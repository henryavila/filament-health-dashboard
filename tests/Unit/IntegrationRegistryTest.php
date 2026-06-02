<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

function fakeIntegration(string $checkName): CheckIntegration
{
    return new class($checkName) implements CheckIntegration
    {
        public function __construct(private string $checkName) {}

        public function checkName(): string
        {
            return $this->checkName;
        }

        public function infolist(StoredCheckResult $result): ?Schema
        {
            return null;
        }

        public function actions(StoredCheckResult $result): array
        {
            return [];
        }
    };
}

test('resolves a registered integration by check name', function (): void {
    $plugin = (new FilamentHealthDashboardPlugin)->integrations([
        fakeIntegration('my_check'),
    ]);

    expect($plugin->resolveIntegration('my_check'))->toBeInstanceOf(CheckIntegration::class)
        ->and($plugin->resolveIntegration('unknown'))->toBeNull();
});

test('keys resolved integrations by their checkName', function (): void {
    $plugin = (new FilamentHealthDashboardPlugin)->integrations([
        fakeIntegration('a'),
        fakeIntegration('b'),
    ]);

    expect(array_keys($plugin->resolvedIntegrations()))->toBe(['a', 'b']);
});

test('exposes fluent navigation + behaviour config', function (): void {
    $plugin = (new FilamentHealthDashboardPlugin)
        ->navigationGroup('Infrastructure')
        ->navigationLabel('Saúde')
        ->navigationBadge(false)
        ->pollingInterval('30s')
        ->showHistory();

    expect($plugin->getNavigationGroup())->toBe('Infrastructure')
        ->and($plugin->getNavigationLabel())->toBe('Saúde')
        ->and($plugin->hasNavigationBadge())->toBeFalse()
        ->and($plugin->getPollingInterval())->toBe('30s')
        ->and($plugin->hasHistory())->toBeTrue();
});

test('authorize defaults to true and honours the callback', function (): void {
    expect((new FilamentHealthDashboardPlugin)->isAuthorized())->toBeTrue()
        ->and((new FilamentHealthDashboardPlugin)->authorize(fn (): bool => false)->isAuthorized())->toBeFalse();
});
