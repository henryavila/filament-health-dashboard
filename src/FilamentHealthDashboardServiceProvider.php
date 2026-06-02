<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard;

use HenryAvila\FilamentHealthDashboard\Widgets\HealthDashboardWidget;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentHealthDashboardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-health-dashboard')
            ->hasViews('filament-health-dashboard');
    }

    public function packageBooted(): void
    {
        // Alias so the dashboard can be embedded in any Blade:
        //   <livewire:filament-health-dashboard />
        Livewire::component('filament-health-dashboard', HealthDashboardWidget::class);
    }
}
