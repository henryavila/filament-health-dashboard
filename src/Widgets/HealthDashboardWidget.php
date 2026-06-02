<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Widgets;

use Filament\Actions\Action;
use Filament\Widgets\Widget;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Throwable;

/**
 * The reusable core of the dashboard. Being a Filament widget, it is also a
 * Livewire component, so it can be used three ways:
 *
 *  1. Placed on any panel page/dashboard via `getWidgets()`/`getHeaderWidgets()`.
 *  2. Embedded in any Blade: `<livewire:filament-health-dashboard />`.
 *  3. Rendered by the package's {@see \HenryAvila\FilamentHealthDashboard\Pages\HealthDashboard} page.
 *
 * All rendering logic lives here so the three surfaces never diverge.
 */
class HealthDashboardWidget extends Widget
{
    protected string $view = 'filament-health-dashboard::widgets.health-dashboard';

    protected int|string|array $columnSpan = 'full';

    /**
     * Re-run all health checks on demand (wire:click).
     */
    public function runChecks(): void
    {
        Artisan::call('health:check');
    }

    public function getLatestResults(): ?StoredCheckResults
    {
        return app(ResultStore::class)->latestResults();
    }

    public function getPollingInterval(): ?string
    {
        return $this->plugin()?->getPollingInterval();
    }

    public function integrationFor(string $checkName): ?CheckIntegration
    {
        return $this->plugin()?->resolveIntegration($checkName);
    }

    /**
     * @return array<int, Action>
     */
    public function integrationActionsFor(StoredCheckResult $result): array
    {
        return $this->integrationFor($result->name)?->actions($result) ?? [];
    }

    /**
     * The plugin is only resolvable inside a panel where it was registered.
     * When the widget is embedded outside that context it degrades gracefully
     * (no integrations, no polling).
     */
    protected function plugin(): ?FilamentHealthDashboardPlugin
    {
        try {
            return FilamentHealthDashboardPlugin::get();
        } catch (Throwable) {
            return null;
        }
    }
}
