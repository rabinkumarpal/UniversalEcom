<?php

namespace Packages\DriverLogistics;

use App\Core\Registry\AddonRegistry;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverDispatchService;
use Packages\DriverLogistics\Services\DriverExceptionService;
use Packages\DriverLogistics\Services\DriverPodService;

class DriverLogisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DriverDispatchService::class, fn () => new DriverDispatchService);
        $this->app->singleton(DriverPodService::class, fn () => new DriverPodService);
        $this->app->singleton(DriverExceptionService::class, fn () => new DriverExceptionService);
    }

    public function boot(): void
    {
        // Load isolated addon migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load addon web routes and views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'driver-logistics');

        // Register addon in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new DriverLogisticsAddon);

        // Dynamically resolve Driver relation on User (preserving core isolation)
        User::resolveRelationUsing('driver', function (User $user) {
            return $user->hasOne(Driver::class);
        });
    }
}
