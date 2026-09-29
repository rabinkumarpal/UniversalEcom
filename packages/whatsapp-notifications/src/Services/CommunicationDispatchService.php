<?php

namespace Packages\WhatsAppNotifications\Services;

use App\Models\Order;
use App\Models\Shipment;
use Packages\WhatsAppNotifications\Models\CommunicationTemplate;
use Packages\WhatsAppNotifications\Models\NotificationLog;

class CommunicationDispatchService
{
    public function __construct(
        protected WhatsAppGatewayClient $gateway
    ) {}

    public function getGateway(): WhatsAppGatewayClient
    {
        return $this->gateway;
    }

    /**
     * Dispatch an outbound notification using a registered template.
     */
    public function send(
        string $phone,
        string $templateKey,
        array $parameters = [],
        array $context = []
    ): NotificationLog {
        // 1. Resolve or auto-seed template
        $template = CommunicationTemplate::where('template_key', $templateKey)->first();

        if (! $template) {
            $defaults = CommunicationTemplate::defaultTemplates();
            $defaultConfig = $defaults[$templateKey] ?? [
                'name' => ucwords(str_replace('_', ' ', $templateKey)),
                'channel' => 'whatsapp',
                'content' => 'Notification for {{order_number}}',
                'variables' => array_keys($parameters),
            ];

            $template = CommunicationTemplate::create([
                'template_key' => $templateKey,
                'channel' => $defaultConfig['channel'] ?? 'whatsapp',
                'name' => $defaultConfig['name'] ?? $templateKey,
                'content' => $defaultConfig['content'],
                'variables' => $defaultConfig['variables'] ?? array_keys($parameters),
                'is_active' => true,
            ]);
        }

        // 2. Render message
        $renderedMessage = $template->render($parameters);
        $normalizedPhone = $this->gateway->normalizePhone($phone);

        // 3. Multi-channel routing: automatic SMS fallback if WhatsApp is disabled
        $channel = $this->gateway->isWhatsAppEnabled() ? ($template->channel ?: 'whatsapp') : 'sms';

        // 4. Send via Gateway
        $gatewayResult = $this->gateway->sendMessage($normalizedPhone, $renderedMessage, [
            'channel' => $channel,
        ]);

        // 5. Record immutable notification log
        return NotificationLog::create([
            'recipient_phone' => $normalizedPhone,
            'recipient_name' => $context['recipient_name'] ?? ($parameters['customer_name'] ?? 'Customer'),
            'channel' => $channel,
            'template_name' => $template->name,
            'parameters' => $parameters,
            'rendered_message' => $renderedMessage,
            'gateway_message_id' => $gatewayResult['message_id'] ?? null,
            'status' => 'sent',
            'order_id' => $context['order_id'] ?? null,
            'shipment_id' => $context['shipment_id'] ?? null,
            'sent_at' => now(),
        ]);
    }

    /**
     * Trigger real-time WhatsApp notification on Order Confirmation.
     */
    public function notifyOrderConfirmed(Order $order): NotificationLog
    {
        $phone = $order->shipping_address_snapshot['phone']
            ?? $order->billing_address_snapshot['phone']
            ?? $order->user?->phone
            ?? '+919999999999';

        $name = ! empty($order->shipping_address_snapshot['first_name'])
            ? trim(($order->shipping_address_snapshot['first_name'] ?? '').' '.($order->shipping_address_snapshot['last_name'] ?? ''))
            : ($order->user?->name ?? 'Valued Customer');

        $total = number_format(($order->grand_total ?? 0) / 100, 2);

        $params = [
            'customer_name' => $name,
            'order_number' => $order->order_number,
            'total_amount' => $total,
            'payment_status' => ucfirst($order->payment_status ?? 'pending'),
            'tracking_url' => url("/account/orders/{$order->id}"),
        ];

        return $this->send($phone, 'order_confirmed', $params, [
            'recipient_name' => $name,
            'order_id' => $order->id,
        ]);
    }

    /**
     * Trigger notification when shipment is dispatched out for delivery with OTP PIN.
     */
    public function notifyShipmentDispatched(Shipment $shipment): NotificationLog
    {
        $order = $shipment->order;
        $phone = $order?->shipping_address_snapshot['phone']
            ?? $order?->user?->phone
            ?? '+919999999999';

        $name = ! empty($order?->shipping_address_snapshot['first_name'])
            ? trim(($order->shipping_address_snapshot['first_name'] ?? '').' '.($order->shipping_address_snapshot['last_name'] ?? ''))
            : ($order?->user?->name ?? 'Valued Customer');

        $driverName = $shipment->carrier_or_driver_name
            ?? $shipment->driver?->user?->name
            ?? 'Assigned Logistics Driver';

        $driverPhone = $shipment->driver_phone
            ?? $shipment->driver?->user?->phone
            ?? 'N/A';

        $params = [
            'customer_name' => $name,
            'order_number' => $order?->order_number ?? "ORD-{$shipment->order_id}",
            'tracking_number' => $shipment->tracking_number ?? "TRK-{$shipment->id}",
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'otp' => $shipment->delivery_otp ?? '123456',
            'tracking_url' => url("/track/{$shipment->tracking_number}"),
        ];

        return $this->send($phone, 'shipment_dispatched', $params, [
            'recipient_name' => $name,
            'order_id' => $order?->id,
            'shipment_id' => $shipment->id,
        ]);
    }

    /**
     * Trigger separate Delivery OTP reminder PIN.
     */
    public function notifyDeliveryOtp(Shipment $shipment): NotificationLog
    {
        $order = $shipment->order;
        $phone = $order?->shipping_address_snapshot['phone']
            ?? $order?->user?->phone
            ?? '+919999999999';

        $params = [
            'otp' => $shipment->delivery_otp ?? '123456',
            'tracking_number' => $shipment->tracking_number ?? "TRK-{$shipment->id}",
            'order_number' => $order?->order_number ?? "ORD-{$shipment->order_id}",
        ];

        return $this->send($phone, 'delivery_otp', $params, [
            'order_id' => $order?->id,
            'shipment_id' => $shipment->id,
        ]);
    }

    /**
     * Trigger Proof of Delivery Handover Receipt.
     */
    public function notifyPodCompleted(Shipment $shipment): NotificationLog
    {
        $order = $shipment->order;
        $phone = $order?->shipping_address_snapshot['phone']
            ?? $order?->user?->phone
            ?? '+919999999999';

        $name = ! empty($order?->shipping_address_snapshot['first_name'])
            ? trim(($order->shipping_address_snapshot['first_name'] ?? '').' '.($order->shipping_address_snapshot['last_name'] ?? ''))
            : ($order?->user?->name ?? 'Valued Customer');

        $deliveredAt = $shipment->delivered_at
            ? $shipment->delivered_at->format('d M Y, h:i A')
            : now()->format('d M Y, h:i A');

        $params = [
            'customer_name' => $name,
            'order_number' => $order?->order_number ?? "ORD-{$shipment->order_id}",
            'tracking_number' => $shipment->tracking_number ?? "TRK-{$shipment->id}",
            'recipient_name' => $shipment->pod_recipient_name ?? 'Recipient',
            'delivered_at' => $deliveredAt,
            'receipt_url' => url("/shipments/{$shipment->id}/pod-receipt"),
        ];

        return $this->send($phone, 'pod_completed', $params, [
            'recipient_name' => $name,
            'order_id' => $order?->id,
            'shipment_id' => $shipment->id,
        ]);
    }

    /**
     * Trigger Delivery Exception Alert.
     */
    public function notifyDeliveryException(Shipment $shipment, string $code, string $notes): NotificationLog
    {
        $order = $shipment->order;
        $phone = $order?->shipping_address_snapshot['phone']
            ?? $order?->user?->phone
            ?? '+919999999999';

        $name = ! empty($order?->shipping_address_snapshot['first_name'])
            ? trim(($order->shipping_address_snapshot['first_name'] ?? '').' '.($order->shipping_address_snapshot['last_name'] ?? ''))
            : ($order?->user?->name ?? 'Valued Customer');

        $params = [
            'customer_name' => $name,
            'tracking_number' => $shipment->tracking_number ?? "TRK-{$shipment->id}",
            'exception_code' => $code,
            'notes' => $notes,
            'tracking_url' => url("/track/{$shipment->tracking_number}"),
        ];

        return $this->send($phone, 'delivery_exception', $params, [
            'recipient_name' => $name,
            'order_id' => $order?->id,
            'shipment_id' => $shipment->id,
        ]);
    }
}
