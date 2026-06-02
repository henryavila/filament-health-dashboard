<?php

declare(strict_types=1);

use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use HenryAvila\FilamentHealthDashboard\Support\HealthDataTable;
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

        public function actions(StoredCheckResult $result): array
        {
            return [];
        }

        public function dataTable(StoredCheckResult $result): ?HealthDataTable
        {
            return null;
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

test('the standalone page is registered by default and can be opted out', function (): void {
    expect((new FilamentHealthDashboardPlugin)->shouldRegisterPage())->toBeTrue()
        ->and((new FilamentHealthDashboardPlugin)->registerPage(false)->shouldRegisterPage())->toBeFalse();
});

test('the page class is swappable', function (): void {
    $custom = HenryAvila\FilamentHealthDashboard\Pages\HealthDashboard::class;

    expect((new FilamentHealthDashboardPlugin)->getPageClass())->toBe($custom)
        ->and((new FilamentHealthDashboardPlugin)->usingPage($custom)->getPageClass())->toBe($custom);
});
