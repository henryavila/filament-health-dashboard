<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

use Illuminate\Support\Str;

/**
 * Heroicon SVG paths used by the dashboard, ported verbatim from the design so
 * the rendering is pixel-identical (outline = UI, solid = status). Rendered as
 * inline SVG to avoid a Blade-icons dependency and keep stroke widths exact.
 */
final class HealthIcons
{
    /** @var array<string, string> outline (24px, stroke 1.5–1.8) */
    private const array OUTLINE = [
        'sync' => 'M16.02 9.35h4.5v-4.5m-15.04 9.8h-4.5v4.5M4.06 9a8.25 8.25 0 0 1 13.8-3.04L20.5 8.6M3.5 15.4l2.65 2.65A8.25 8.25 0 0 0 19.94 15',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'chevronD' => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'chevronU' => 'm4.5 15.75 7.5-7.5 7.5 7.5',
        'chevronR' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'x' => 'M6 18 18 6M6 6l12 12',
        'arrowR' => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
        'ellipsis' => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
        'info' => 'm11.25 11.25.04-.02a.75.75 0 0 1 1.06.85l-.7 2.84a.75.75 0 0 0 1.06.85l.04-.02M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.01v.01H12V8.25Z',
        'doc' => 'M19.5 14.25v-2.63c0-3.81-2.4-7.04-5.76-8.31a.6.6 0 0 0-.24-.06H6.75A2.25 2.25 0 0 0 4.5 5.5v13a2.25 2.25 0 0 0 2.25 2.25h10.5A2.25 2.25 0 0 0 19.5 18.5v-4.25Zm0 0h-3.38a1.13 1.13 0 0 1-1.12-1.13V9.75',
        'history' => 'M12 6v6l4 2m5.5-2a9.5 9.5 0 1 1-3.3-7.2M21.5 4v4.5H17',
        'cpu' => 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z',
        'database' => 'M20.25 6.38c0 2.27-3.7 4.12-8.25 4.12S3.75 8.65 3.75 6.38m16.5 0C20.25 4.1 16.55 2.25 12 2.25S3.75 4.1 3.75 6.38m16.5 0v11.25c0 2.27-3.7 4.12-8.25 4.12s-8.25-1.85-8.25-4.12V6.38m16.5 5.62c0 2.28-3.7 4.13-8.25 4.13S3.75 14.28 3.75 12',
        'server' => 'M21.75 17.25v-.23a4.5 4.5 0 0 0-.12-1.03l-2.27-9.08a3.75 3.75 0 0 0-3.64-2.84H8.28a3.75 3.75 0 0 0-3.64 2.84l-2.27 9.08a4.5 4.5 0 0 0-.12 1.03v.23m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.01v.01h-.01v-.01Zm-3 0h.01v.01h-.01v-.01Z',
        'bolt' => 'M3.75 13.5 14.25 3v6.75h6L9.75 21v-7.5h-6Z',
        'queue' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.63 4.5h12.74a1.88 1.88 0 0 1 0 3.75H5.63a1.88 1.88 0 0 1 0-3.75Z',
        'wrench' => 'M11.42 15.17 17.25 21A2.65 2.65 0 1 0 21 17.25l-5.88-5.88m-3.7 3.8a4.5 4.5 0 0 1-1.42-7.37l2.86 2.86a.5.5 0 0 0 .7 0l2.5-2.5a.5.5 0 0 0 0-.7L13.4 4.6a4.5 4.5 0 0 1 6.06 5.43m-12.04 5.14L4.6 18a2.12 2.12 0 1 0 3 3l2.23-2.82',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'archive' => 'M20.25 7.5l-.63 10.63a2.25 2.25 0 0 1-2.24 2.12H6.62a2.25 2.25 0 0 1-2.24-2.12L3.75 7.5M10 11.25h4M3.38 7.5h17.25c.62 0 1.12-.5 1.12-1.13v-1.5c0-.62-.5-1.12-1.12-1.12H3.38c-.62 0-1.13.5-1.13 1.13v1.5c0 .62.5 1.12 1.13 1.12Z',
        'shield' => 'M9 12.75 11.25 15 15 9.75m-3-7.04a11.96 11.96 0 0 1-8.62 3.67 12.02 12.02 0 0 0 8.62 14.62 12.02 12.02 0 0 0 8.62-14.62A11.96 11.96 0 0 1 12 2.71Z',
        'squares' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Zm9.75-9.75A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        'inbox' => 'M2.25 13.5h3.86c.46 0 .89.32 1.06.78a3.75 3.75 0 0 0 7.06 0c.17-.46.6-.78 1.06-.78h3.86m-16.5 0V8.66c0-.51.1-1 .3-1.46l2.6-6.09m13 7.55V8.66c0-.51-.1-1-.3-1.46m0 0-2.6-6.09M4.85 1.11A1.5 1.5 0 0 1 6.23.18h11.54c.6 0 1.14.36 1.38.93m-14.3 0 .35.82',
        'pulse' => 'M3 12h4l2-6 4 12 2-6h6',
        'shieldcheck' => 'M9 12.75 11.25 15 15 9.75m-3-7.04a11.96 11.96 0 0 1-8.62 3.67 12.02 12.02 0 0 0 8.62 14.62 12.02 12.02 0 0 0 8.62-14.62A11.96 11.96 0 0 1 12 2.71Z',
    ];

    /** @var array<string, string> solid (status) */
    private const array SOLID = [
        'ok' => 'M2.25 12c0-5.39 4.36-9.75 9.75-9.75s9.75 4.36 9.75 9.75-4.36 9.75-9.75 9.75S2.25 17.39 2.25 12Zm13.36-1.81a.75.75 0 1 0-1.22-.87l-3.24 4.53-1.63-1.63a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.09l3.75-5.25Z',
        'warn' => 'M9.4 3c1.16-2 4.05-2 5.2 0l7.36 12.75c1.15 2-.29 4.5-2.6 4.5H4.65c-2.31 0-3.76-2.5-2.6-4.5L9.4 3ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z',
        'fail' => 'M12 2.25c-5.39 0-9.75 4.36-9.75 9.75s4.36 9.75 9.75 9.75 9.75-4.36 9.75-9.75S17.39 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z',
        'skip' => 'M12 2.25c-5.39 0-9.75 4.36-9.75 9.75s4.36 9.75 9.75 9.75 9.75-4.36 9.75-9.75S17.39 2.25 12 2.25ZM8.25 12a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z',
        'heart' => 'M11.65 21.18C6.7 18.56 2.25 14.65 2.25 9.94 2.25 6.6 4.86 4.5 7.5 4.5c1.6 0 3.18.78 4.5 2.34C13.32 5.28 14.9 4.5 16.5 4.5c2.64 0 5.25 2.1 5.25 5.44 0 4.71-4.45 8.62-9.4 11.24a.75.75 0 0 1-.7 0Z',
        'info' => 'M2.25 12c0-5.39 4.36-9.75 9.75-9.75s9.75 4.36 9.75 9.75-4.36 9.75-9.75 9.75S2.25 17.39 2.25 12Zm8.7-3.04a1.05 1.05 0 1 1 2.1 0 1.05 1.05 0 0 1-2.1 0ZM12 10.5a.75.75 0 0 1 .73.93l-.93 3.72.06-.03a.75.75 0 0 1 .67 1.34l-.06.04a1.575 1.575 0 0 1-2.22-1.79l.93-3.72-.06.03a.75.75 0 0 1-.67-1.34l.06-.04c.262-.131.553-.2.84-.2H12Z',
    ];

    public static function outline(string $name, int $size = 20, float $stroke = 1.7): string
    {
        $d = self::OUTLINE[$name] ?? self::OUTLINE['pulse'];

        return sprintf(
            '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="%3$s"/></svg>',
            $size,
            $stroke,
            $d,
        );
    }

    public static function solid(string $name, int $size = 20): string
    {
        $d = self::SOLID[$name] ?? self::SOLID['skip'];

        return sprintf(
            '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="%2$s"/></svg>',
            $size,
            $d,
        );
    }

    /**
     * Resolve a domain icon name from a health check name (heuristic).
     */
    public static function iconFor(string $checkName): string
    {
        $n = Str::lower($checkName);

        return match (true) {
            str_contains($n, 'cpu') => 'cpu',
            str_contains($n, 'disk'), str_contains($n, 'space') => 'server',
            str_contains($n, 'database'), str_contains($n, 'db_'), $n === 'db' => 'database',
            str_contains($n, 'redis'), str_contains($n, 'cache') => 'bolt',
            str_contains($n, 'horizon') => 'queue',
            str_contains($n, 'queue') => 'squares',
            str_contains($n, 'stale'), str_contains($n, 'fqcn'), str_contains($n, 'class') => 'wrench',
            str_contains($n, 'schedule'), str_contains($n, 'cron') => 'calendar',
            str_contains($n, 'backup') => 'archive',
            str_contains($n, 'debug'), str_contains($n, 'security'), str_contains($n, 'ssl'), str_contains($n, 'cert'), str_contains($n, 'advisor') => 'shield',
            str_contains($n, 'mail'), str_contains($n, 'smtp') => 'inbox',
            str_contains($n, 'ping'), str_contains($n, 'http'), str_contains($n, 'url'), str_contains($n, 'api') => 'pulse',
            str_contains($n, 'schedule'), str_contains($n, 'env') => 'doc',
            default => 'pulse',
        };
    }
}
