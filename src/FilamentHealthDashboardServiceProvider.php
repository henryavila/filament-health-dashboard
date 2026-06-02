<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard;

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
}
