<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use UnitEnum;

/**
 * Actionable health dashboard. Renders the latest spatie/laravel-health results
 * (status grid + per-check `meta` drill-down) and, per check, any host-registered
 * {@see CheckIntegration} (rich infolist + remediation actions).
 */
class HealthDashboard extends Page
{
    protected string $view = 'filament-health-dashboard::pages.health-dashboard';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return FilamentHealthDashboardPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return FilamentHealthDashboardPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentHealthDashboardPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return FilamentHealthDashboardPlugin::get()->getNavigationLabel() ?? 'Health';
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! FilamentHealthDashboardPlugin::get()->hasNavigationBadge()) {
            return null;
        }

        $failing = static::failingCount();

        return $failing > 0 ? (string) $failing : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function canAccess(): bool
    {
        return FilamentHealthDashboardPlugin::get()->isAuthorized();
    }

    /**
     * Re-run all health checks on demand (populates the result store).
     */
    public function runChecks(): void
    {
        Artisan::call('health:check');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('Verificar agora'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->runChecks()),
        ];
    }

    public function getLatestResults(): ?StoredCheckResults
    {
        return app(ResultStore::class)->latestResults();
    }

    public function getPollingInterval(): ?string
    {
        return FilamentHealthDashboardPlugin::get()->getPollingInterval();
    }

    /**
     * Host integration for a given check, if registered.
     */
    public function integrationFor(string $checkName): ?CheckIntegration
    {
        return FilamentHealthDashboardPlugin::get()->resolveIntegration($checkName);
    }

    /**
     * @return array<int, Action>
     */
    public function integrationActionsFor(StoredCheckResult $result): array
    {
        return $this->integrationFor($result->name)?->actions($result) ?? [];
    }

    protected static function failingCount(): int
    {
        $results = app(ResultStore::class)->latestResults();

        if ($results === null) {
            return 0;
        }

        return $results->storedCheckResults
            ->filter(fn (StoredCheckResult $result): bool => in_array(
                $result->status,
                ['failed', 'crashed'],
                true,
            ))
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'results' => $this->getLatestResults(),
            'pollingInterval' => $this->getPollingInterval(),
        ];
    }
}
