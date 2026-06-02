# Filament Health Dashboard

[![Latest tag](https://img.shields.io/github/v/tag/henryavila/filament-health-dashboard?label=release&sort=semver)](https://github.com/henryavila/filament-health-dashboard/tags)
[![Filament v4](https://img.shields.io/badge/Filament-v4-f59e0b)](https://filamentphp.com)
[![License: MIT](https://img.shields.io/github/license/henryavila/filament-health-dashboard)](LICENSE)

An **actionable** [Filament v4](https://filamentphp.com) dashboard for
[`spatie/laravel-health`](https://github.com/spatie/laravel-health).

Most health dashboards are a read-only mirror of the status page. This one goes
further: it surfaces each check's `meta` (drill-down), shows a failing-count
navigation badge, can live as a page **or** a widget **or** an embeddable
component, and exposes an **integration layer** so your domain checks can offer
rich detail and one-click remediation — without coupling the package to your app.

```php
// Minimal: a status dashboard on your panel
$panel->plugin(FilamentHealthDashboardPlugin::make());
```

---

## Table of contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Where the dashboard appears (3 surfaces)](#where-the-dashboard-appears)
- [Configuration](#configuration)
- [Integration layer](#integration-layer)
- [Customizing the view](#customizing-the-view)
- [Testing](#testing)
- [License](#license)

## Features

- **Status grid** of every registered health check (color + icon + message).
- **Per-check `meta` drill-down** — the generic spatie `Result->meta` that most
  dashboards throw away, rendered in the most readable shape automatically
  (scalar → inline, nested → pretty JSON, **list of records → a table**). See
  [Metadata rendering](#metadata-rendering).
- **Failing-count navigation badge** on the dashboard menu item.
- **Three surfaces**: standalone page, widget, or embeddable Livewire component.
- **Integration layer** (`CheckIntegration`): per-check rich infolist + actions.
- **Optional polling** (auto-refresh) and a manual "re-run checks" button.
- **Authorization** hook and **publishable views**.

## Requirements

| | |
|---|---|
| PHP | 8.3+ |
| Filament | v4 |
| spatie/laravel-health | ^1.30 |

## Installation

```bash
composer require henryavila/filament-health-dashboard
```

You also need `spatie/laravel-health` configured: register your checks (e.g. in a
service provider via `Health::checks([...])`) and a result store such as
`EloquentHealthResultStore`. Schedule `php artisan health:check` so the store
stays fresh.

> **Styling is self-contained.** The dashboard inlines its own stylesheet, so
> there is **no `php artisan filament:assets` step** — it renders correctly the
> moment the plugin is registered (including path-symlinked dev installs). Fonts
> resolve to Filament's bundled Inter and a system monospace fallback; no
> external font CDN is fetched.

## Quick start

Register the plugin on a panel:

```php
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(
        FilamentHealthDashboardPlugin::make()
            ->navigationGroup('Infrastructure')
            ->navigationIcon('heroicon-o-heart'),
    );
}
```

That's it — a "Health" item appears in the panel navigation with the status grid.

## Where the dashboard appears

The core is a Livewire widget, so the same dashboard can appear three ways. Pick
any combination.

### 1. Standalone page (default)

Its own navigation item and route, registered by the plugin. Opt out if you only
want the widget/component:

```php
FilamentHealthDashboardPlugin::make()->registerPage(false);

// or swap the page to customize navigation/slug:
FilamentHealthDashboardPlugin::make()->usingPage(\App\Filament\Pages\MyHealth::class);
```

### 2. Widget

Drop it onto any page or dashboard:

```php
use HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget;

protected function getHeaderWidgets(): array
{
    return [HealthDashboardWidget::class];
}
```

### 3. Embeddable Livewire component

Render it inside any Blade — your own page, a modal, a tab:

```blade
<livewire:filament-health-dashboard />
```

## Configuration

All methods are chained on `FilamentHealthDashboardPlugin::make()`:

| Method | Description |
|---|---|
| `navigationGroup(?string)` | Navigation group for the page. |
| `navigationIcon(string\|BackedEnum\|null)` | Navigation icon. |
| `navigationLabel(?string)` | Navigation label (default `Health`). |
| `navigationSort(?int)` | Navigation order. |
| `navigationBadge(bool = true)` | Show the failing-count badge (default on). |
| `pollingInterval(?string)` | Auto-refresh interval, e.g. `'60s'`. |
| `authorize(Closure)` | Gate access to the dashboard. |
| `registerPage(bool = true)` | Register the standalone page + nav item. |
| `usingPage(class-string)` | Use a custom page class. |
| `integrations(array)` | Register per-check integrations (see below). |

## Metadata rendering

Every check's `Result->meta` is rendered automatically in the drill-down — no
per-app code — picking the most readable shape per value:

| Meta value shape | Rendered as |
|---|---|
| Scalar (`string`/`int`/`bool`/`null`) | inline value |
| **List of records** (`[{...}, {...}]` — list of associative arrays) | **a table** (columns = union of keys, one row per record) |
| Any other nested array/object | pretty-printed, scrollable JSON block |

The table case is the high-value one: checks that store a list of rows as meta
(e.g. `spatie/security-advisories-health-check` stores one advisory object per
row) would otherwise read as a single 2 KB JSON line. The detection
([`MetaTable::tryFrom()`](src/Support/MetaTable.php)) is fully generic, so any
check in any app gets the table for free — just put a list of records in `meta`:

```php
Result::make()->meta([
    'advisories' => [
        ['package' => 'acme/foo', 'cve' => 'CVE-2026-1', 'severity' => 'high'],
        ['package' => 'acme/bar', 'cve' => 'CVE-2026-2', 'severity' => 'medium'],
    ],
]);
// → renders a 3-column table (package · cve · severity), 2 rows.
```

Need something richer than meta (status-coloured rows, remediation buttons,
a bespoke layout)? Use the integration layer below.

## Integration layer

The dashboard renders every check generically. To add domain value to a specific
check, implement `CheckIntegration` and register it via `->integrations([...])`.
It is matched to a result by `checkName()` (equal to the spatie check's name).

```php
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

final class MyCheckIntegration implements CheckIntegration
{
    public function checkName(): string
    {
        return 'my_check';
    }

    public function infolist(StoredCheckResult $result): ?Schema
    {
        // Rich drill-down, or null to fall back to the generic meta table.
        return null;
    }

    public function actions(StoredCheckResult $result): array
    {
        return [
            Action::make('remediate')
                ->label('Fix it')
                ->requiresConfirmation()
                ->action(fn () => /* run your command */ null),
        ];
    }
}
```

```php
FilamentHealthDashboardPlugin::make()->integrations([
    MyCheckIntegration::class,
]);
```

## Customizing the view

Publish the views and edit them in your app:

```bash
php artisan vendor:publish --tag=filament-health-dashboard-views
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [LICENSE](LICENSE).
