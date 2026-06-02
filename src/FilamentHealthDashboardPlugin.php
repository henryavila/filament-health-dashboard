<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use HenryAvila\FilamentHealthDashboard\Contracts\CheckIntegration;
use HenryAvila\FilamentHealthDashboard\Pages\HealthDashboard;

class FilamentHealthDashboardPlugin implements Plugin
{
    public const string ID = 'filament-health-dashboard';

    /**
     * @var array<int, class-string<CheckIntegration>|CheckIntegration>
     */
    protected array $integrations = [];

    /**
     * @var array<string, CheckIntegration>|null
     */
    protected ?array $resolvedIntegrations = null;

    protected bool $registerPage = true;

    /**
     * @var class-string<HealthDashboard>
     */
    protected string $pageClass = HealthDashboard::class;

    protected ?string $navigationGroup = null;

    protected string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected ?int $navigationSort = null;

    protected ?string $navigationLabel = null;

    protected bool $navigationBadge = true;

    protected ?string $pollingInterval = null;

    protected bool $showHistory = false;

    protected ?Closure $authorizeUsing = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getPlugin(self::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->registerPage) {
            $panel->pages([
                $this->pageClass,
            ]);
        }
    }

    public function boot(Panel $panel): void {}

    /**
     * Whether to register the standalone page (and its nav item). Disable when
     * you only want the widget / Livewire component.
     */
    public function registerPage(bool $condition = true): static
    {
        $this->registerPage = $condition;

        return $this;
    }

    public function shouldRegisterPage(): bool
    {
        return $this->registerPage;
    }

    /**
     * Swap the page class (e.g. to extend HealthDashboard with custom nav/slug).
     *
     * @param  class-string<HealthDashboard>  $pageClass
     */
    public function usingPage(string $pageClass): static
    {
        $this->pageClass = $pageClass;

        return $this;
    }

    /**
     * @return class-string<HealthDashboard>
     */
    public function getPageClass(): string
    {
        return $this->pageClass;
    }

    /**
     * @param  array<int, class-string<CheckIntegration>|CheckIntegration>  $integrations
     */
    public function integrations(array $integrations): static
    {
        $this->integrations = $integrations;
        $this->resolvedIntegrations = null;

        return $this;
    }

    public function resolveIntegration(string $checkName): ?CheckIntegration
    {
        return $this->resolvedIntegrations()[$checkName] ?? null;
    }

    /**
     * @return array<string, CheckIntegration>
     */
    public function resolvedIntegrations(): array
    {
        if ($this->resolvedIntegrations !== null) {
            return $this->resolvedIntegrations;
        }

        $resolved = [];

        foreach ($this->integrations as $integration) {
            $instance = $integration instanceof CheckIntegration ? $integration : app($integration);
            $resolved[$instance->checkName()] = $instance;
        }

        return $this->resolvedIntegrations = $resolved;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function navigationIcon(string|\BackedEnum|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string|\BackedEnum|null
    {
        return $this->navigationIcon;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel;
    }

    public function navigationBadge(bool $condition = true): static
    {
        $this->navigationBadge = $condition;

        return $this;
    }

    public function hasNavigationBadge(): bool
    {
        return $this->navigationBadge;
    }

    public function pollingInterval(?string $interval): static
    {
        $this->pollingInterval = $interval;

        return $this;
    }

    public function getPollingInterval(): ?string
    {
        return $this->pollingInterval;
    }

    public function showHistory(bool $condition = true): static
    {
        $this->showHistory = $condition;

        return $this;
    }

    public function hasHistory(): bool
    {
        return $this->showHistory;
    }

    public function authorize(Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if ($this->authorizeUsing === null) {
            return true;
        }

        return (bool) ($this->authorizeUsing)();
    }
}
