<?php

namespace App\Providers;

use App\Core\Contracts\ShippingRateContract;
use App\Core\Contracts\TaxCalculatorContract;
use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Core\Registry\PricingAdjustmentRegistry;
use App\Core\Registry\ShippingProviderRegistry;
use App\Core\Services\AuditService;
use App\Core\Services\SettingService;
use App\Domain\Delivery\DeliveryService;
use App\Domain\Payments\CashOnDeliveryGateway;
use App\Domain\Pricing\PricingPipeline;
use App\Domain\Pricing\StandardTaxCalculator;
use Illuminate\Support\ServiceProvider;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditService::class, fn () => new AuditService);
        $this->app->singleton(SettingService::class, fn () => new SettingService);
        $this->app->singleton(AddonRegistry::class, fn ($app) => new AddonRegistry($app));
        $this->app->singleton(PaymentGatewayRegistry::class, fn () => new PaymentGatewayRegistry);
        $this->app->singleton(ShippingProviderRegistry::class, fn () => new ShippingProviderRegistry);
        $this->app->singleton(PricingAdjustmentRegistry::class, fn () => new PricingAdjustmentRegistry);

        $this->app->bind(TaxCalculatorContract::class, StandardTaxCalculator::class);

        $this->app->bind(ShippingRateContract::class, function ($app) {
            return $app->make(ShippingProviderRegistry::class)->get();
        });

        $this->app->singleton(PricingPipeline::class, function ($app) {
            return new PricingPipeline(
                $app->make(PricingAdjustmentRegistry::class),
                $app->make(ShippingProviderRegistry::class),
                $app->make(TaxCalculatorContract::class)
            );
        });
    }

    public function boot(): void
    {
        // Register default delivery / shipping provider
        $shippingRegistry = $this->app->make(ShippingProviderRegistry::class);
        $shippingRegistry->register(new DeliveryService, true);

        // Register default payment gateway
        $paymentRegistry = $this->app->make(PaymentGatewayRegistry::class);
        $paymentRegistry->register(new CashOnDeliveryGateway);

        // Boot all registered portable add-ons after ALL providers have booted
        $this->app->booted(function () {
            $this->app->make(AddonRegistry::class)->bootAll();
        });
    }
}
