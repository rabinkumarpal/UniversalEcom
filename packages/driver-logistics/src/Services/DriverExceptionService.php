<?php

namespace Packages\DriverLogistics\Services;

use App\Models\DeliveryException;
use App\Models\Shipment;
use Packages\DriverLogistics\Models\Driver;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;

class DriverExceptionService
{
    /**
     * Report an on-site delivery impediment from the mobile driver app.
     */
    public function recordException(Shipment $shipment, Driver $driver, string $code, string $notes): DeliveryException
    {
        $exception = DeliveryException::create([
            'shipment_id' => $shipment->id,
            'exception_code' => $code,
            'notes' => $notes,
            'recorded_by_user_id' => $driver->user_id,
            'is_resolved' => false,
        ]);

        $shipment->update([
            'status' => 'failed',
            'notes' => "[Exception: {$code}] {$notes}",
        ]);

        if (class_exists(CommunicationDispatchService::class) && app()->bound(CommunicationDispatchService::class)) {
            app(CommunicationDispatchService::class)->notifyDeliveryException($shipment, $code, $notes);
        }

        return $exception;
    }
}
