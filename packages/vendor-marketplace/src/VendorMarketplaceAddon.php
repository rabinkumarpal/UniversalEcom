<?php

namespace Packages\VendorMarketplace;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class VendorMarketplaceAddon implements AddonInterface
{
    public function id(): string
    {
        return 'vendor-marketplace';
    }

    public function name(): string
    {
        return 'Vendor Marketplace & Multi-Vendor Fulfillment';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Multi-vendor marketplace support with independent vendor inventory, multi-vendor cart, order splitting, commissions and payouts.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.vendor_marketplace.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Marketplace boot logic
    }
}
