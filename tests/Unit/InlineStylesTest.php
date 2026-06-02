<?php

declare(strict_types=1);

use HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget;

/*
 * Regression guard: the dashboard must ship its stylesheet inline so it renders
 * correctly with no `php artisan filament:assets` publish step. The previous
 * FilamentAsset::register() approach left a 404 <link> (and an unstyled page)
 * whenever the host skipped publishing — e.g. a path-symlinked dev install.
 */
test('inlines the self-contained stylesheet (no filament:assets publish needed)', function (): void {
    $css = (new HealthDashboardWidget)->inlineStyles();

    expect($css)
        // design tokens the blade references via var(--…) must be defined
        ->toContain('--fg-1:')
        ->toContain('--bg-surface:')
        ->toContain('--success:')
        ->toContain('--hd-card-pad:')
        // component classes used throughout the view
        ->toContain('.h-card')
        ->toContain('.hd-root')
        // dark-mode token overrides
        ->toContain(':where(.dark)')
        // must stay offline-safe: no external font CDN / @import url() rule
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('@import url');
});
