<?php

namespace Packages\DriverLogistics\Services;

use App\Core\Events\OrderStatusUpdated;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use Packages\DriverLogistics\Models\Driver;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;
use RuntimeException;

class DriverPodService
{
    /**
     * Authoritatively record Proof of Delivery (POD) from the mobile driver app.
     */
    public function submitProofOfDelivery(Shipment $shipment, Driver $driver, array $podData): Shipment
    {
        // 1. Authorize: driver must be assigned driver or admin
        if ($shipment->driver_id && $shipment->driver_id !== $driver->id) {
            if (! $driver->user || ! $driver->user->hasRole('admin')) {
                throw new RuntimeException('Unauthorized: You are not the assigned driver for this shipment.');
            }
        }

        // 2. Verify Delivery OTP if set
        if (! empty($shipment->delivery_otp)) {
            $providedOtp = trim($podData['otp'] ?? '');
            if ($providedOtp !== $shipment->delivery_otp) {
                throw new RuntimeException('Invalid Delivery OTP. Please request the correct 6-digit confirmation PIN from the recipient.');
            }
        }

        $recipientName = trim($podData['recipient_name'] ?? '');
        if (empty($recipientName)) {
            throw new RuntimeException('Recipient name is required for Proof of Delivery.');
        }

        $latitude = isset($podData['latitude']) ? (float) $podData['latitude'] : null;
        $longitude = isset($podData['longitude']) ? (float) $podData['longitude'] : null;

        // 3. Update Shipment Record
        $shipment->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'pod_recipient_name' => $recipientName,
            'pod_signature' => 'Captured via Driver App',
            'pod_signature_data' => $podData['signature_data'] ?? null,
            'pod_otp' => $podData['otp'] ?? $shipment->delivery_otp,
            'pod_photo_path' => $podData['photo_path'] ?? null,
            'pod_latitude' => $latitude,
            'pod_longitude' => $longitude,
            'notes' => $podData['notes'] ?? $shipment->notes,
        ]);

        // 4. Update Order Status, History & Events
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
                'note' => 'Delivered to '.$recipientName.' via Driver Mobile App (POD Recorded).',
                'user_id' => $driver->user_id,
            ]);

            event(new OrderStatusUpdated(
                $order,
                $previousStatus,
                'delivered',
                $driver->user_id,
                'Delivered to '.$recipientName.' via Driver Mobile App (POD Recorded).'
            ));
        }

        // 5. Update Driver Last Known Location
        if ($latitude !== null && $longitude !== null) {
            $driver->updateLocation($latitude, $longitude);
        }

        $freshShipment = $shipment->fresh(['order', 'driver']);

        if (class_exists(CommunicationDispatchService::class) && app()->bound(CommunicationDispatchService::class)) {
            app(CommunicationDispatchService::class)->notifyPodCompleted($freshShipment);
        }

        return $freshShipment;
    }
}
