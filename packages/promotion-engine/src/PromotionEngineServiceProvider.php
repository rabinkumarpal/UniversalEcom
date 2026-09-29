<?php

namespace Packages\PromotionEngine;

use App\Core\Events\OrderPlaced;
use App\Core\Registry\AddonRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Packages\PromotionEngine\Services\PromotionCalculationService;

class PromotionEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PromotionCalculationService::class, fn () => new PromotionCalculationService);
    }

    public function boot(): void
    {
        // Register migrations from addon
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register addon in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new PromotionEngineAddon);

        // Listen to OrderPlaced to record usage and trigger next order coupons
        Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
            $calcService = $this->app->make(PromotionCalculationService::class);
            $calcService->recordOrderApplication($event->order, $event->metadata);
        });
    }
}
