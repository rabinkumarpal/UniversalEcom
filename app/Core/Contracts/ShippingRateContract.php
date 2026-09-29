<?php

namespace App\Core\Contracts;

use App\Models\Address;
use App\Models\Cart;

interface ShippingRateContract
{
    /**
     * Unique provider identifier (e.g. 'local-zones', 'custom-carrier').
     */
    public function id(): string;

    /**
     * Display name for storefront.
     */
    public function name(): string;

    /**
     * Check if a destination pincode / address is serviceable.
     */
    public function isServiceable(string $pincode, ?Address $address = null): bool;

    /**
     * Calculate delivery fee in minor units (cents / paise) based on cart and destination.
     */
    public function calculateRate(Cart $cart, ?Address $address = null): int;

    /**
     * Available delivery slots for the given date/pincode.
     */
    public function getAvailableSlots(string $pincode, ?string $date = null): array;
}
