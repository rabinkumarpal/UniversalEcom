<?php

namespace Packages\DriverLogistics\Services;

use App\Models\Shipment;
use Illuminate\Support\Str;
use Packages\DriverLogistics\Models\Driver;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;

class DriverDispatchService
{
    /**
     * Assign a driver to a shipment, generate secure delivery OTP, and dispatch out for delivery.
     */
    public function assignDriverToShipment(Shipment $shipment, Driver $driver, array $options = []): Shipment
    {
        $otp = $shipment->delivery_otp ?: sprintf('%06d', random_int(100000, 999999));
        $trackingNumber = $shipment->tracking_number ?: ('TRK-'.strtoupper(Str::random(8)));

        $shipment->update([
            'driver_id' => $driver->id,
            'carrier_or_driver_name' => $driver->user?->name ?? 'Fleet Driver',
            'driver_phone' => $driver->user?->phone ?? ($options['driver_phone'] ?? null),
            'tracking_number' => $trackingNumber,
            'status' => 'out_for_delivery',
            'delivery_otp' => $otp,
            'dispatched_at' => now(),
            'notes' => $options['notes'] ?? $shipment->notes,
        ]);

        $order = $shipment->order;
        if ($order && in_array($order->status, ['confirmed', 'picking', 'packed', 'pending'])) {
            $order->update([
                'status' => 'out_for_delivery',
                'fulfillment_status' => 'fulfilled',
            ]);
        }

        $freshShipment = $shipment->fresh(['driver.user', 'order']);

        if (class_exists(CommunicationDispatchService::class) && app()->bound(CommunicationDispatchService::class)) {
            app(CommunicationDispatchService::class)->notifyShipmentDispatched($freshShipment);
        }

        return $freshShipment;
    }
}
