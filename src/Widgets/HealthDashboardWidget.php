<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Widgets;

use Filament\Widgets\Widget;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use HenryAvila\FilamentHealthDashboard\Support\HealthHistory;
use HenryAvila\FilamentHealthDashboard\Support\HealthIcons;
use HenryAvila\FilamentHealthDashboard\Support\HealthStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Throwable;

/**
 * The reusable core of the dashboard (a Filament widget = Livewire component).
 * Renders the pixel-perfect health UI: stat cards, status grid, history heatmap
 * and a drill-down modal. Usable as page, widget, or `<livewire:filament-health-dashboard />`.
 */
class HealthDashboardWidget extends Widget
{
    protected string $view = 'filament-health-dashboard::widgets.health-dashboard';

    protected int|string|array $columnSpan = 'full';

    // ---- actions ----------------------------------------------------

    /** Re-run all health checks (header button). */
    public function runChecks(): void
    {
        Artisan::call('health:check');
    }

    /** Run a single integration action for a check. */
    public function runAction(string $checkName, string $key): void
    {
        $integration = $this->integrationFor($checkName);
        $result = $this->findResult($checkName);

        if ($integration === null || $result === null) {
            return;
        }

        foreach ($integration->actions($result) as $action) {
            if ($action->key === $key && $action->authorized) {
                $action->run();

                return;
            }
        }
    }

    // ---- view model -------------------------------------------------

    public function isEmpty(): bool
    {
        return $this->latest()?->storedCheckResults->isEmpty() ?? true;
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $c = ['total' => 0, 'ok' => 0, 'warn' => 0, 'fail' => 0, 'skip' => 0];

        foreach ($this->storedResults() as $r) {
            $c['total']++;
            $c[HealthStatus::key($r->status)]++;
        }

        return $c;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function checks(): array
    {
        $heatmaps = $this->safeHeatmaps();

        return $this->storedResults()
            ->map(fn (StoredCheckResult $r): array => $this->presentCheck($r, $heatmaps[$r->name] ?? null))
            ->values()
            ->all();
    }

    /**
     * Daily status counts over 30 days for the stat-card sparklines.
     *
     * @return array{total: list<int>, ok: list<int>, warn: list<int>, fail: list<int>}
     */
    public function sparklines(): array
    {
        $heatmaps = $this->safeHeatmaps();
        $spark = ['total' => [], 'ok' => [], 'warn' => [], 'fail' => []];

        for ($day = 0; $day < HealthHistory::DAYS; $day++) {
            $tally = ['total' => 0, 'ok' => 0, 'warn' => 0, 'fail' => 0];

            foreach ($heatmaps as $row) {
                $status = $row[$day] ?? null;

                if ($status === null) {
                    continue;
                }

                $tally['total']++;
                $key = HealthStatus::key($status);

                if (isset($tally[$key])) {
                    $tally[$key]++;
                }
            }

            foreach ($spark as $k => $_) {
                $spark[$k][] = $tally[$k];
            }
        }

        return $spark;
    }

    public function lastRanLabel(): ?string
    {
        $endedAt = $this->latest()?->finishedAt;

        if ($endedAt === null) {
            return null;
        }

        $minutes = (int) Date::instance($endedAt)->diffInMinutes(Date::now());

        return $minutes <= 1 ? 'há 1 min' : sprintf('há %d min', $minutes);
    }

    public function isStale(): bool
    {
        $endedAt = $this->latest()?->finishedAt;

        if ($endedAt === null) {
            return false;
        }

        return Date::instance($endedAt)->lt(Date::now()->subMinutes(5));
    }

    public function getPollingInterval(): ?string
    {
        return $this->plugin()?->getPollingInterval();
    }

    public function title(): string
    {
        return $this->plugin()?->getNavigationLabel() ?? 'Saúde';
    }

    public function subtitle(): string
    {
        return 'Resultado das verificações de saúde da aplicação e da infraestrutura.';
    }

    // ---- internals --------------------------------------------------

    /**
     * @param  list<string|null>|null  $history
     * @return array<string, mixed>
     */
    private function presentCheck(StoredCheckResult $r, ?array $history): array
    {
        $integration = $this->integrationFor($r->name);

        $actions = [];
        $dataTable = null;

        if ($integration !== null) {
            foreach ($integration->actions($r) as $action) {
                if ($action->authorized) {
                    $actions[] = $action->toArray();
                }
            }

            $dataTable = $integration->dataTable($r)?->toArray();
        }

        return [
            'name' => $r->name,
            'label' => $r->label !== '' ? $r->label : $r->name,
            'status' => $r->status,
            'statusKey' => HealthStatus::key($r->status),
            'icon' => HealthIcons::iconFor($r->name),
            'summary' => $r->shortSummary !== '' ? $r->shortSummary : (string) $r->notificationMessage,
            'message' => $r->notificationMessage !== '' ? (string) $r->notificationMessage : $r->shortSummary,
            'lastRan' => $this->lastRanLabel() ?? '—',
            'meta' => $this->formatMeta($r->meta),
            'history' => $history ?? array_fill(0, HealthHistory::DAYS, null),
            'actions' => $actions,
            'dataTable' => $dataTable,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, string>
     */
    private function formatMeta(array $meta): array
    {
        $out = [];

        foreach ($meta as $key => $value) {
            $out[(string) $key] = match (gettype($value)) {
                'array', 'object' => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'boolean' => $value ? 'true' : 'false',
                'NULL' => '—',
                default => (string) $value,
            };
        }

        return $out;
    }

    /**
     * @return \Illuminate\Support\Collection<int, StoredCheckResult>
     */
    private function storedResults(): \Illuminate\Support\Collection
    {
        return $this->latest()?->storedCheckResults ?? collect();
    }

    private function findResult(string $checkName): ?StoredCheckResult
    {
        return $this->storedResults()->first(fn (StoredCheckResult $r): bool => $r->name === $checkName);
    }

    private function latest(): ?StoredCheckResults
    {
        return app(ResultStore::class)->latestResults();
    }

    /**
     * @return array<string, list<string|null>>
     */
    private function safeHeatmaps(): array
    {
        try {
            return HealthHistory::heatmaps();
        } catch (Throwable) {
            return [];
        }
    }

    private function integrationFor(string $checkName): ?CheckIntegration
    {
        return $this->plugin()?->resolveIntegration($checkName);
    }

    private function plugin(): ?FilamentHealthDashboardPlugin
    {
        try {
            return FilamentHealthDashboardPlugin::get();
        } catch (Throwable) {
            return null;
        }
    }
}
