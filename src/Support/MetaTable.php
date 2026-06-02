<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

/**
 * A table auto-derived from a check's raw metadata — the generic counterpart to
 * {@see HealthDataTable} (which an integration builds by hand). Many checks
 * store a list of records as meta (e.g. SecurityAdvisories: one advisory object
 * per row); that reads terribly as a single JSON line but maps cleanly to
 * columns/rows. {@see tryFrom()} recognises that shape so the dashboard renders
 * a table for ANY such check with no per-app integration code.
 */
final class MetaTable
{
    /**
     * @param  list<string>  $columns  union of record keys, in first-seen order
     * @param  list<list<string>>  $rows  each row's cells aligned to $columns
     */
    public function __construct(
        public readonly array $columns,
        public readonly array $rows,
    ) {}

    /**
     * Build a table when (and only when) the value is a non-empty list whose
     * every element is a non-empty associative array (a "record"). Any other
     * shape returns null, so the caller falls back to scalar / pretty-JSON.
     */
    public static function tryFrom(mixed $value): ?self
    {
        if (gettype($value) !== 'array' || $value === []) {
            return null;
        }

        /** @var array<array-key, mixed> $value */
        if (! array_is_list($value)) {
            return null;
        }

        $columns = [];

        foreach ($value as $row) {
            // A tabular value is a list of records: every element must be a
            // non-empty associative array. Bail on anything else.
            if (gettype($row) !== 'array' || $row === [] || array_is_list($row)) {
                return null;
            }

            /** @var array<array-key, mixed> $row */
            foreach (array_keys($row) as $key) {
                $column = (string) $key;

                if (! in_array($column, $columns, true)) {
                    $columns[] = $column;
                }
            }
        }

        $rows = [];

        foreach ($value as $row) {
            /** @var array<array-key, mixed> $row */
            $cells = [];

            foreach ($columns as $column) {
                $cells[] = array_key_exists($column, $row) ? self::cell($row[$column]) : '';
            }

            $rows[] = $cells;
        }

        return new self($columns, $rows);
    }

    /**
     * Stringify a single cell. Nested structures collapse to compact JSON
     * (a cell stays on one line); scalars render naturally.
     */
    private static function cell(mixed $value): string
    {
        return match (gettype($value)) {
            'array', 'object' => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'boolean' => $value ? 'true' : 'false',
            'NULL' => '—',
            default => (string) $value,
        };
    }
}
