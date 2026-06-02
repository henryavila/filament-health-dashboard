<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Contracts;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

/**
 * Host-side integration for a single health check.
 *
 * The dashboard renders every spatie/laravel-health result generically (status
 * + message + a key/value table of `meta`). Register a CheckIntegration to add
 * domain value to a specific check: a richer drill-down (infolist) and/or
 * remediation actions — without coupling this package to the host app.
 *
 * Implementations are registered on the panel via
 * `FilamentHealthDashboardPlugin::make()->integrations([...])` and matched to a
 * result by {@see checkName()} (equal to the spatie check's registered name).
 */
interface CheckIntegration
{
    /**
     * The health check name this integration applies to
     * (matches StoredCheckResult::$name).
     */
    public function checkName(): string;

    /**
     * Rich drill-down schema (rendered as an infolist in a modal). Return null
     * to fall back to the generic `meta` key/value table.
     */
    public function infolist(StoredCheckResult $result): ?Schema;

    /**
     * Domain/remediation actions shown for this check (already authorized by the
     * implementation). Return an empty array for none.
     *
     * @return array<int, Action>
     */
    public function actions(StoredCheckResult $result): array;
}
