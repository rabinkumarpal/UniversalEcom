<?php

namespace Packages\PromotionEngine;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;
use App\Core\Registry\PricingAdjustmentRegistry;
use Packages\PromotionEngine\Services\PromotionCalculationService;

class PromotionEngineAddon implements AddonInterface
{
    public function id(): string
    {
        return 'promotion-engine';
    }

    public function name(): string
    {
        return 'Advanced Promotion & Discount Engine';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Authoritative promotion engine supporting BOGO, bundles, quantity tiers, coupons, spending goals and loyalty rewards.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.promotion_engine.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Register adjustment provider into core pricing pipeline
        $registry = $context->app->make(PricingAdjustmentRegistry::class);
        $registry->register(new PromotionCalculationService);
    }
}
