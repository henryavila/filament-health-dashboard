<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Widgets;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;
use HenryAvila\FilamentHealthDashboard\Support\HealthHistory;
use HenryAvila\FilamentHealthDashboard\Support\HealthIcons;
use HenryAvila\FilamentHealthDashboard\Support\HealthStatus;
use HenryAvila\FilamentHealthDashboard\Support\MetaTable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

    /** Drill-down modal sizing — width scales with a check's widest table. */
    private const int MODAL_WIDTH_DEFAULT = 720;

    private const int MODAL_WIDTH_MAX = 1680;

    private const int MODAL_PER_COLUMN_PX = 160;

    private const int MODAL_CHROME_PX = 96;

    /**
     * The dashboard stylesheet, inlined into the widget output so the UI is
     * self-contained: it renders pixel-correct wherever it is mounted (page,
     * widget slot, or `<livewire:filament-health-dashboard />`) without the
     * host having to run `php artisan filament:assets`. Read once per request.
     */
    public function inlineStyles(): string
    {
        static $css = null;

        return $css ??= (string) file_get_contents(__DIR__ . '/../../resources/css/health-dashboard.css');
    }

    // ---- actions ----------------------------------------------------

    private const string RUN_LOCK_KEY = 'filament-health-dashboard:run-checks';

    /** Re-run all health checks (header button). */
    public function runChecks(): void
    {
        // This Livewire method is publicly callable, so re-check the same gate
        // the page uses instead of trusting the UI.
        if (! ($this->plugin()?->isAuthorized() ?? false)) {
            return;
        }

        // Debounce concurrent runs (spam-clicks / parallel tabs) so a fresh
        // click never piles a second `health:check` on top of one in flight.
        $lock = Cache::lock(self::RUN_LOCK_KEY, 60);

        if (! $lock->get()) {
            Notification::make()->title('Uma verificação já está em andamento.')->warning()->send();

            return;
        }

        try {
            // Artisan::call() is typed `: int` (the command exit code) — a
            // non-zero code is a failure, so the run no longer reads as success
            // in the UI regardless of outcome.
            $exitCode = Artisan::call('health:check');

            $this->notifyOutcome(
                $exitCode === 0,
                'Verificações executadas.',
                'Falha ao executar as verificações.',
            );
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Falha ao executar as verificações.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $lock->release();
        }
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
                $failureTitle = sprintf('%s — falhou.', $action->label);

                // The handler is an opaque side-effecting closure, so a thrown
                // exception is the failure signal — caught here so a failing
                // action surfaces an error instead of silently reading as done.
                try {
                    $action->run();
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->title($failureTitle)->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(sprintf('%s — concluído.', $action->label))->success()->send();

                return;
            }
        }
    }

    private function notifyOutcome(bool $ok, string $successTitle, string $failureTitle): void
    {
        if ($ok) {
            Notification::make()->title($successTitle)->success()->send();

            return;
        }

        Notification::make()->title($failureTitle)->danger()->send();
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
        $dataTableColumns = 0;

        if ($integration !== null) {
            foreach ($integration->actions($r) as $action) {
                if ($action->authorized) {
                    $actions[] = $action->toArray();
                }
            }

            $table = $integration->dataTable($r);
            $dataTable = $table?->toArray();
            $dataTableColumns = $table === null ? 0 : count($table->columns);
        }

        $meta = $this->presentMeta($r->meta);

        return [
            'name' => $r->name,
            'label' => $r->label !== '' ? $r->label : $r->name,
            'status' => $r->status,
            'statusKey' => HealthStatus::key($r->status),
            'icon' => HealthIcons::iconFor($r->name),
            'summary' => $r->shortSummary !== '' ? $r->shortSummary : (string) $r->notificationMessage,
            'message' => $r->notificationMessage !== '' ? (string) $r->notificationMessage : $r->shortSummary,
            'lastRan' => $this->lastRanLabel() ?? '—',
            'meta' => $meta,
            'history' => $history ?? array_fill(0, HealthHistory::DAYS, null),
            'actions' => $actions,
            'dataTable' => $dataTable,
            // Drill-down width is derived from the widest table the check carries
            // (see modalWidthFor) so many-column content gets room. The view caps
            // it to the viewport.
            'modalWidth' => $this->modalWidthFor($meta, $dataTableColumns),
        ];
    }

    /**
     * Drill-down modal width (px), scaled to the widest table the check carries.
     * A check with no table keeps the comfortable default; otherwise the width
     * grows with the column count so dense tables (e.g. SecurityAdvisories'
     * ~12-column advisory list) get space instead of being crammed. Generic —
     * driven purely by structure (column count), not by any specific check. The
     * view caps the result to the viewport (`min(..px, 95vw)`) and the table
     * still scrolls horizontally if even that is not enough.
     *
     * @param  list<array{label: string, kind: string, text: string|null, table: MetaTable|null}>  $meta
     */
    private function modalWidthFor(array $meta, int $dataTableColumns): int
    {
        $maxColumns = $dataTableColumns;

        foreach ($meta as $entry) {
            if ($entry['table'] !== null) {
                $maxColumns = max($maxColumns, count($entry['table']->columns));
            }
        }

        if ($maxColumns === 0) {
            return self::MODAL_WIDTH_DEFAULT;
        }

        // ~160px per column + chrome, clamped to a sane band.
        $estimate = self::MODAL_CHROME_PX + $maxColumns * self::MODAL_PER_COLUMN_PX;

        return max(self::MODAL_WIDTH_DEFAULT, min(self::MODAL_WIDTH_MAX, $estimate));
    }

    /**
     * Present check metadata for the modal, picking the most readable shape per
     * value — fully generic, so any check benefits with zero per-app code:
     *   • list of records  → a table ({@see MetaTable})
     *   • nested array/obj → pretty-printed JSON (view renders it in a <pre>)
     *   • scalar           → inline value
     *
     * @param  array<string, mixed>  $meta
     * @return list<array{label: string, kind: 'scalar'|'json'|'table', text: string|null, table: MetaTable|null}>
     */
    private function presentMeta(array $meta): array
    {
        $entries = [];

        foreach ($meta as $key => $value) {
            $label = (string) $key;
            $table = MetaTable::tryFrom($value);

            if ($table !== null) {
                $entries[] = ['label' => $label, 'kind' => 'table', 'text' => null, 'table' => $table];

                continue;
            }

            $entries[] = match (gettype($value)) {
                // Nested structure that is NOT a clean list of records: pretty-
                // print so the indented JSON is readable instead of one line.
                'array', 'object' => [
                    'label' => $label,
                    'kind' => 'json',
                    'text' => (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'table' => null,
                ],
                'boolean' => ['label' => $label, 'kind' => 'scalar', 'text' => $value ? 'true' : 'false', 'table' => null],
                'NULL' => ['label' => $label, 'kind' => 'scalar', 'text' => '—', 'table' => null],
                default => ['label' => $label, 'kind' => 'scalar', 'text' => (string) $value, 'table' => null],
            };
        }

        return $entries;
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
