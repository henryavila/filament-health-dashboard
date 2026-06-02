<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Contracts;

use HenryAvila\FilamentHealthDashboard\Support\HealthAction;
use HenryAvila\FilamentHealthDashboard\Support\HealthDataTable;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

/**
 * Host-side integration for a single health check — the extension point that
 * adds domain value (remediation actions + a rich data table) to an otherwise
 * generic check, without coupling this package to the host app.
 *
 * Registered via `FilamentHealthDashboardPlugin::make()->integrations([...])`
 * and matched to a result by {@see checkName()} (== the spatie check's name).
 */
interface CheckIntegration
{
    /**
     * The health check name this integration applies to (== StoredCheckResult::$name).
     */
    public function checkName(): string;

    /**
     * Remediation actions for this check (card footer + modal). Already
     * authorized by the implementation. Empty array for none.
     *
     * @return array<int, HealthAction>
     */
    public function actions(StoredCheckResult $result): array;

    /**
     * A rich data table for the drill-down modal (e.g. the offending rows).
     * Null falls back to the generic meta key/value table only.
     */
    public function dataTable(StoredCheckResult $result): ?HealthDataTable;
}
