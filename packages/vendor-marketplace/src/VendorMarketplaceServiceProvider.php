<?php

namespace Packages\VendorMarketplace;

use App\Core\Events\OrderPlaced;
use App\Core\Registry\AddonRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Packages\VendorMarketplace\Services\VendorOrderSplittingService;

class VendorMarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VendorOrderSplittingService::class, fn () => new VendorOrderSplittingService);
    }

    public function boot(): void
    {
        // Load addon migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new VendorMarketplaceAddon);

        // Load package web routes and views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vendor-marketplace');

        // Listen to OrderPlaced for automatic multi-vendor order splitting
        Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
            $splittingService = $this->app->make(VendorOrderSplittingService::class);
            $splittingService->splitOrder($event->order);
        });
    }
}
