<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

/**
 * Single source of truth for status presentation, ported from the design.
 * Maps spatie statuses → key/label/icon/CSS color vars. crashed→fail, unknown→skip.
 */
final class HealthStatus
{
    /** @var array<string, array{key: string, label: string, icon: string, color: string, bg: string}> */
    private const array MAP = [
        'ok' => ['key' => 'ok', 'label' => 'OK', 'icon' => 'ok', 'color' => 'var(--st-ok)', 'bg' => 'var(--st-ok-bg)'],
        'warning' => ['key' => 'warn', 'label' => 'Atenção', 'icon' => 'warn', 'color' => 'var(--st-warn)', 'bg' => 'var(--st-warn-bg)'],
        'failed' => ['key' => 'fail', 'label' => 'Falhando', 'icon' => 'fail', 'color' => 'var(--st-fail)', 'bg' => 'var(--st-fail-bg)'],
        'crashed' => ['key' => 'fail', 'label' => 'Falhando', 'icon' => 'fail', 'color' => 'var(--st-fail)', 'bg' => 'var(--st-fail-bg)'],
        'skipped' => ['key' => 'skip', 'label' => 'Ignorado', 'icon' => 'skip', 'color' => 'var(--st-skip)', 'bg' => 'var(--st-skip-bg)'],
        'unknown' => ['key' => 'skip', 'label' => 'Desconhecido', 'icon' => 'skip', 'color' => 'var(--st-skip)', 'bg' => 'var(--st-skip-bg)'],
    ];

    /** Worst-of-day ranking for the heatmap. */
    private const array RANK = ['failed' => 4, 'crashed' => 4, 'warning' => 3, 'ok' => 2, 'skipped' => 1, 'unknown' => 0];

    /**
     * @return array{key: string, label: string, icon: string, color: string, bg: string}
     */
    public static function of(string $status): array
    {
        return self::MAP[$status] ?? self::MAP['unknown'];
    }

    public static function key(string $status): string
    {
        return self::of($status)['key'];
    }

    public static function rank(string $status): int
    {
        return self::RANK[$status] ?? 0;
    }

    public static function isFailing(string $status): bool
    {
        return self::key($status) === 'fail';
    }

    /** Heatmap cell color by status key. */
    public static function heatColor(string $status): string
    {
        return match (self::key($status)) {
            'ok' => 'var(--success)',
            'warn' => 'var(--warning)',
            'fail' => 'var(--danger)',
            default => 'var(--gray-300)',
        };
    }
}
