<?php

namespace Packages\B2BCommerce;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Core\Registry\PricingAdjustmentRegistry;
use Packages\B2BCommerce\Gateways\PurchaseOrderGateway;
use Packages\B2BCommerce\Services\B2BContractPricingAdjuster;

class B2BCommerceAddon implements AddonInterface
{
    public function id(): string
    {
        return 'b2b-commerce';
    }

    public function name(): string
    {
        return 'B2B Commerce, Corporate Accounts & Contract Pricing';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Multi-tier corporate companies, role-based spending limits, negotiated contract price lists, credit limits, purchase orders, and supervisor approval workflows.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.b2b_commerce.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        $gatewayRegistry = app(PaymentGatewayRegistry::class);
        $gatewayRegistry->register(app(PurchaseOrderGateway::class));

        $pricingRegistry = app(PricingAdjustmentRegistry::class);
        $pricingRegistry->register(app(B2BContractPricingAdjuster::class));
    }
}
