<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Pages;

use BackedEnum;
use Filament\Pages\Page;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Throwable;
use UnitEnum;

/**
 * Standalone page surface: a thin shell that renders the
 * {@see \HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget} and
 * owns the navigation item (group/icon/badge from the plugin config).
 *
 * Registration is opt-in via `FilamentHealthDashboardPlugin::registerPage()`;
 * hosts that only want the widget/Livewire component can disable it.
 *
 * The plugin is resolved defensively: this page's static methods can be
 * evaluated under a panel where the plugin isn't registered (global
 * search / spotlight "from all panels"), so {@see plugin()} returns null
 * there and {@see canAccess()} correctly reports the page as unavailable.
 */
class HealthDashboard extends Page
{
    protected string $view = 'filament-health-dashboard::pages.health-dashboard';

    protected static function plugin(): ?FilamentHealthDashboardPlugin
    {
        try {
            return FilamentHealthDashboardPlugin::get();
        } catch (Throwable) {
            return null;
        }
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::plugin()?->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::plugin()?->getNavigationIcon() ?? 'heroicon-o-heart';
    }

    public static function getNavigationSort(): ?int
    {
        return static::plugin()?->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return static::plugin()?->getNavigationLabel() ?? 'Health';
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    /**
     * The widget renders its own h1 (matching the design), so suppress the
     * Filament page heading to avoid a duplicate title.
     */
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public static function getNavigationBadge(): ?string
    {
        $plugin = static::plugin();

        if ($plugin === null || ! $plugin->hasNavigationBadge()) {
            return null;
        }

        $failing = static::failingCount();

        return $failing > 0 ? (string) $failing : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    /**
     * Only accessible where the plugin is registered (i.e. its panel). Returns
     * false elsewhere so cross-panel indexing doesn't surface or crash on it.
     */
    public static function canAccess(): bool
    {
        return static::plugin()?->isAuthorized() ?? false;
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
}
