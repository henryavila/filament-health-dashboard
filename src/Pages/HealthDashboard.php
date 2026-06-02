<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Pages;

use BackedEnum;
use Filament\Pages\Page;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use UnitEnum;

/**
 * Standalone page surface: a thin shell that renders the
 * {@see \HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget} and
 * owns the navigation item (group/icon/badge from the plugin config).
 *
 * Registration is opt-in via `FilamentHealthDashboardPlugin::registerPage()`;
 * hosts that only want the widget/Livewire component can disable it.
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
