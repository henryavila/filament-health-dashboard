<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Tests;

use HenryAvila\FilamentHealthDashboard\FilamentHealthDashboardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FilamentHealthDashboardServiceProvider::class,
        ];
    }
}
