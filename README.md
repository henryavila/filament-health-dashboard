# Filament Health Dashboard

An **actionable** [Filament v4](https://filamentphp.com) dashboard for
[`spatie/laravel-health`](https://github.com/spatie/laravel-health).

Unlike a read-only status mirror, this package surfaces each check's `meta`
(drill-down), a failing-count navigation badge, and a host-pluggable
**integration layer** so domain checks can offer rich detail + remediation
actions — without coupling the package to your app.

## Features

- **Status grid** of every registered health check (color + icon + message).
- **Per-check `meta` drill-down** (the generic spatie `Result->meta`, which most
  dashboards discard).
- **Failing-count navigation badge** on the dashboard menu item.
- **Optional polling** (auto-refresh) and a manual "re-run checks" action.
- **Authorization** hook.
- **Integration layer** (`CheckIntegration`): register, per check, a rich
  infolist drill-down and remediation actions.

## Requirements

- PHP 8.3+
- Filament v4
- `spatie/laravel-health` ^1.30

## Installation

```bash
composer require henryavila/filament-health-dashboard
```

Make sure `spatie/laravel-health` is installed and you have registered your
checks (typically in a service provider via `Health::checks([...])`) and a result
store (e.g. `EloquentHealthResultStore`). Run `php artisan health:check` on a
schedule so the store stays fresh.

## Usage

Register the plugin on a panel:

```php
use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(
            FilamentHealthDashboardPlugin::make()
                ->navigationGroup('Infrastructure')
                ->navigationIcon('heroicon-o-heart')
                ->navigationBadge()        // failing-count badge (default: on)
                ->pollingInterval('60s')   // optional auto-refresh
                ->showHistory()            // reserved for the history view
                ->authorize(fn (): bool => auth()->user()?->can('view-health') ?? false)
                ->integrations([
                    \App\Health\Integrations\MyCheckIntegration::class,
                ]),
        );
}
```

### Where the dashboard is shown — three surfaces

The same core (a Livewire widget) can appear anywhere:

**1. Standalone page** (default) — its own navigation item + route. Registered by
the plugin. Disable it if you only want the widget/component:

```php
FilamentHealthDashboardPlugin::make()->registerPage(false);
// optionally swap the page to customize nav/slug:
FilamentHealthDashboardPlugin::make()->usingPage(\App\Filament\Pages\MyHealth::class);
```

**2. Widget** — drop it on any page or dashboard:

```php
use HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget;

protected function getHeaderWidgets(): array
{
    return [HealthDashboardWidget::class];
}
```

**3. Livewire component** — embed it in any Blade (your own page, a modal, a tab):

```blade
<livewire:filament-health-dashboard />
```

The `CheckIntegration` drill-down/actions work identically across all three,
because they live in the widget core.

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
        // Return a rich drill-down, or null to fall back to the generic meta table.
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

## License

MIT. See [LICENSE](LICENSE).
