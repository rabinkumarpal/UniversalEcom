<?php

namespace App\Domain\Delivery;

use App\Core\Contracts\ShippingRateContract;
use App\Models\Address;
use App\Models\Cart;
use App\Models\DeliverySlot;
use App\Models\DeliveryZonePincode;

class DeliveryService implements ShippingRateContract
{
    public function id(): string
    {
        return 'local-zones';
    }

    public function name(): string
    {
        return 'Local Zone Delivery';
    }

    /**
     * Check if a pincode is serviceable.
     */
    public function isServiceable(string $pincode, ?Address $address = null): bool
    {
        return DeliveryZonePincode::where('pincode', trim($pincode))
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))
            ->exists();
    }

    /**
     * Calculate delivery fee in minor units (cents / paise).
     */
    public function calculateRate(Cart $cart, ?Address $address = null): int
    {
        $pincode = $address?->pincode ?? request()->query('pincode');

        if (! $pincode) {
            return 0;
        }

        $zonePincode = DeliveryZonePincode::with('zone')
            ->where('pincode', trim($pincode))
            ->first();

        if (! $zonePincode || ! $zonePincode->zone || ! $zonePincode->zone->is_active) {
            return 5000; // Default flat fee (₹50.00 / $50.00)
        }

        $zone = $zonePincode->zone;
        $subtotal = $cart->raw_subtotal;

        // Check if free shipping threshold is met
        if ($zone->min_order_free_shipping !== null && $subtotal >= $zone->min_order_free_shipping) {
            return 0;
        }

        return $zone->base_fee;
    }

    /**
     * Get available delivery slots for a pincode.
     */
    public function getAvailableSlots(string $pincode, ?string $date = null): array
    {
        $zonePincode = DeliveryZonePincode::where('pincode', trim($pincode))->first();

        if (! $zonePincode) {
            return [];
        }

        return DeliverySlot::where('delivery_zone_id', $zonePincode->delivery_zone_id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'name' => $slot->name,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ])
            ->toArray();
    }
}
