<?php

namespace Packages\DriverLogistics;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class DriverLogisticsAddon implements AddonInterface
{
    public function id(): string
    {
        return 'driver-logistics';
    }

    public function name(): string
    {
        return 'Driver Fleet Management & Proof of Delivery (POD)';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Driver fleet profiles, mobile-first delivery portal, live route manifests, digital POD (OTP verification, digital signature canvas, site unloading photo, GPS), and real-time delivery exception reporting.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.driver_logistics.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Boot hooks for delivery logistics
    }
}
