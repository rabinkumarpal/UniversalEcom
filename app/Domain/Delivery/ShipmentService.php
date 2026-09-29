<?php

namespace App\Domain\Delivery;

use App\Core\Events\OrderStatusUpdated;
use App\Models\DeliveryException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use Illuminate\Support\Str;

class ShipmentService
{
    /**
     * Create a shipment for an order.
     */
    public function createShipmentForOrder(Order $order, array $data = []): Shipment
    {
        $shipmentNumber = 'SHP-'.date('Ymd').'-'.strtoupper(Str::random(4));

        return Shipment::create([
            'order_id' => $order->id,
            'shipment_number' => $shipmentNumber,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'carrier_or_driver_name' => $data['carrier_or_driver_name'] ?? null,
            'driver_phone' => $data['driver_phone'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Dispatch shipment out for delivery with driver details.
     */
    public function dispatchShipment(Shipment $shipment, array $driverData): Shipment
    {
        $otp = $driverData['delivery_otp'] ?? ($shipment->delivery_otp ?: sprintf('%06d', random_int(100000, 999999)));

        $shipment->update([
            'status' => 'out_for_delivery',
            'driver_id' => $driverData['driver_id'] ?? $shipment->driver_id,
            'carrier_or_driver_name' => $driverData['carrier_or_driver_name'] ?? $shipment->carrier_or_driver_name,
            'driver_phone' => $driverData['driver_phone'] ?? $shipment->driver_phone,
            'tracking_number' => $driverData['tracking_number'] ?? ('TRK-'.strtoupper(Str::random(8))),
            'delivery_otp' => $otp,
            'dispatched_at' => now(),
            'notes' => $driverData['notes'] ?? $shipment->notes,
        ]);

        $order = $shipment->order;
        if ($order && in_array($order->status, ['confirmed', 'picking', 'packed'])) {
            $order->update([
                'status' => 'out_for_delivery',
                'fulfillment_status' => 'fulfilled',
            ]);
        }

        return $shipment->fresh();
    }

    /**
     * Record proof of delivery (POD) with recipient, OTP/signature, and GPS coordinates.
     */
    public function recordProofOfDelivery(Shipment $shipment, array $podData): Shipment
    {
        $shipment->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'pod_recipient_name' => $podData['pod_recipient_name'] ?? null,
            'pod_signature' => $podData['pod_signature'] ?? null,
            'pod_otp' => $podData['pod_otp'] ?? null,
            'pod_photo_path' => $podData['pod_photo_path'] ?? null,
            'pod_latitude' => $podData['pod_latitude'] ?? null,
            'pod_longitude' => $podData['pod_longitude'] ?? null,
            'notes' => $podData['notes'] ?? $shipment->notes,
        ]);

        $order = $shipment->order;
        if ($order) {
            $previousStatus = $order->status;
            $order->update([
                'status' => 'delivered',
                'fulfillment_status' => 'fulfilled',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => 'delivered',
                'note' => 'Delivered to '.($podData['pod_recipient_name'] ?? 'Recipient').' (POD Recorded).',
                'user_id' => auth()->id(),
            ]);

            event(new OrderStatusUpdated(
                $order,
                $previousStatus,
                'delivered',
                auth()->id(),
                'Delivered to '.($podData['pod_recipient_name'] ?? 'Recipient').' (POD Recorded).'
            ));
        }

        return $shipment->fresh();
    }

    /**
     * Record a delivery exception for the shipment.
     */
    public function recordException(Shipment $shipment, string $exceptionCode, string $notes, ?int $userId = null): DeliveryException
    {
        $validCodes = [
            DeliveryException::CODE_CUSTOMER_UNAVAILABLE,
            DeliveryException::CODE_WRONG_ADDRESS,
            DeliveryException::CODE_PINCODE_NOT_SERVICEABLE,
            DeliveryException::CODE_STOCK_SHORTAGE,
            DeliveryException::CODE_VEHICLE_ISSUE,
        ];

        if (! in_array($exceptionCode, $validCodes)) {
            $exceptionCode = DeliveryException::CODE_CUSTOMER_UNAVAILABLE;
        }

        $exception = DeliveryException::create([
            'shipment_id' => $shipment->id,
            'exception_code' => $exceptionCode,
            'notes' => $notes,
            'recorded_by_user_id' => $userId,
            'is_resolved' => false,
        ]);

        $shipment->update([
            'status' => 'failed',
        ]);

        return $exception;
    }
}
