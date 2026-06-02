<?php

declare(strict_types=1);

use HenryAvila\FilamentHealthDashboard\Support\MetaTable;
use HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget;

/*
 * The package's value is surfacing check metadata, so it picks the most
 * readable shape per value — generically, with zero per-app code:
 *   • list of records  → a table (MetaTable)
 *   • nested array/obj → pretty-printed (multi-line) JSON
 *   • scalar           → a clean single-line string
 */
function presentMeta(array $meta): array
{
    $method = new ReflectionMethod(HealthDashboardWidget::class, 'presentMeta');
    $method->setAccessible(true);

    return $method->invoke(new HealthDashboardWidget, $meta);
}

// ---- MetaTable::tryFrom (the generic shape detector) --------------------

test('MetaTable builds columns/rows from a list of records', function (): void {
    $table = MetaTable::tryFrom([
        ['cve' => 'CVE-1', 'severity' => 'medium'],
        ['cve' => 'CVE-2', 'severity' => 'high'],
    ]);

    expect($table)->toBeInstanceOf(MetaTable::class)
        ->and($table->columns)->toBe(['cve', 'severity'])
        ->and($table->rows)->toBe([
            ['CVE-1', 'medium'],
            ['CVE-2', 'high'],
        ]);
});

test('MetaTable unions keys across records and fills gaps with empty cells', function (): void {
    $table = MetaTable::tryFrom([
        ['a' => '1', 'b' => '2'],
        ['a' => '3', 'c' => '4'],
    ]);

    expect($table->columns)->toBe(['a', 'b', 'c'])
        ->and($table->rows)->toBe([
            ['1', '2', ''],
            ['3', '', '4'],
        ]);
});

test('MetaTable collapses a nested cell value to compact JSON', function (): void {
    $table = MetaTable::tryFrom([
        ['name' => 'x', 'sources' => [['id' => 1]]],
    ]);

    expect($table->rows[0][1])->toBe('[{"id":1}]');
});

test('MetaTable returns null for non-tabular shapes', function (mixed $value): void {
    expect(MetaTable::tryFrom($value))->toBeNull();
})->with([
    'scalar' => 'just a string',
    'empty array' => [[]],
    'list of scalars' => [['a', 'b', 'c']],
    'associative (single record, not a list)' => [['key' => 'value']],
    'list with a scalar element' => [[['ok' => 1], 'oops']],
]);

// ---- presentMeta (per-value classification) -----------------------------

test('presentMeta renders a list of records as a table entry', function (): void {
    $entries = presentMeta([
        'phpoffice/phpspreadsheet' => [
            ['cve' => 'CVE-2026-40902', 'severity' => 'medium'],
            ['cve' => 'CVE-2026-35453', 'severity' => 'medium'],
        ],
    ]);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['kind'])->toBe('table')
        ->and($entries[0]['label'])->toBe('phpoffice/phpspreadsheet')
        ->and($entries[0]['table'])->toBeInstanceOf(MetaTable::class)
        ->and($entries[0]['table']->rows)->toHaveCount(2);
});

test('presentMeta pretty-prints a nested (non-tabular) value as multi-line JSON', function (): void {
    $entries = presentMeta([
        'config' => ['nested' => ['deep' => true]],
    ]);

    expect($entries[0]['kind'])->toBe('json')
        ->and($entries[0]['text'])->toContain("\n")->toContain('    ');
});

test('modal width scales with the widest table, capped, default when no table', function (): void {
    $modalWidthFor = new ReflectionMethod(HealthDashboardWidget::class, 'modalWidthFor');
    $modalWidthFor->setAccessible(true);
    $widget = new HealthDashboardWidget;

    $scalarOnly = presentMeta(['severity' => 'high', 'count' => 3]);
    $threeCol = presentMeta(['rows' => [['a' => 1, 'b' => 2, 'c' => 3]]]);
    $manyCol = presentMeta(['rows' => [array_fill_keys(range(1, 12), 'x')]]);

    // no table → comfortable default
    expect($modalWidthFor->invoke($widget, $scalarOnly, 0))->toBe(720)
        // a table → grows with column count (96 + cols*160), floored at 720
        ->and($modalWidthFor->invoke($widget, $threeCol, 0))->toBe(720)   // 96+3*160=576 → floor 720
        ->and($modalWidthFor->invoke($widget, $scalarOnly, 6))->toBe(1056) // dataTable cols also count: 96+6*160
        // many columns → clamped to the max band
        ->and($modalWidthFor->invoke($widget, $manyCol, 0))->toBe(1680);   // 96+12*160=2016 → cap 1680
});

test('presentMeta keeps scalars as clean single-line strings', function (): void {
    $entries = presentMeta([
        'severity' => 'medium',
        'enabled' => true,
        'missing' => null,
        'count' => 7,
    ]);

    expect($entries[0])->toMatchArray(['kind' => 'scalar', 'text' => 'medium'])
        ->and($entries[1]['text'])->toBe('true')
        ->and($entries[2]['text'])->toBe('—')
        ->and($entries[3]['text'])->toBe('7');
});
