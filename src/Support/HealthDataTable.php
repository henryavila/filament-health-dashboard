<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

/**
 * A rich data table rendered inside the drill-down modal (e.g. the stale-class
 * references). Provided by a {@see \HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration}.
 */
final class HealthDataTable
{
    /**
     * @param  list<string>  $columns
     * @param  list<HealthDataRow>  $rows
     */
    public function __construct(
        public readonly array $columns,
        public readonly array $rows,
        public readonly ?string $title = null,
    ) {}

    /**
     * @return array{title: string|null, columns: list<string>, rows: list<array{cells: list<string>, status: string, fixable: bool}>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'columns' => $this->columns,
            'rows' => array_map(fn (HealthDataRow $r): array => $r->toArray(), $this->rows),
        ];
    }
}
