<?php

namespace Packages\WhatsAppNotifications\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    protected $table = 'communication_templates';

    protected $fillable = [
        'template_key',
        'channel',
        'name',
        'content',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Render the template string with provided data parameters.
     */
    public function render(array $data): string
    {
        $rendered = $this->content;
        foreach ($data as $key => $value) {
            $rendered = str_replace('{{'.$key.'}}', (string) $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Predefined standard communication templates for heavy/universal commerce.
     */
    public static function defaultTemplates(): array
    {
        return [
            'order_confirmed' => [
                'name' => 'Order Confirmation',
                'channel' => 'whatsapp',
                'content' => "Hello {{customer_name}},\n\nYour order *{{order_number}}* has been confirmed! Total: ₹{{total_amount}} (Payment: {{payment_status}}).\n\nTrack your order here: {{tracking_url}}\n\nThank you for choosing us!",
                'variables' => ['customer_name', 'order_number', 'total_amount', 'payment_status', 'tracking_url'],
            ],
            'shipment_dispatched' => [
                'name' => 'Consignment Out for Delivery',
                'channel' => 'whatsapp',
                'content' => "Hello {{customer_name}},\n\nYour shipment *{{tracking_number}}* for order *{{order_number}}* is out for delivery with driver *{{driver_name}}* (Ph: {{driver_phone}}).\n\nYour secure 6-digit Delivery OTP is: *{{otp}}*\n(Please share this OTP with the driver only upon inspecting your delivery).\n\nLive Track: {{tracking_url}}",
                'variables' => ['customer_name', 'order_number', 'tracking_number', 'driver_name', 'driver_phone', 'otp', 'tracking_url'],
            ],
            'delivery_otp' => [
                'name' => 'Delivery OTP Verification',
                'channel' => 'whatsapp',
                'content' => 'Security PIN: Your Delivery OTP is *{{otp}}* for shipment *{{tracking_number}}*. Share only upon complete unloading and verification.',
                'variables' => ['otp', 'tracking_number', 'order_number'],
            ],
            'pod_completed' => [
                'name' => 'Proof of Delivery Completed',
                'channel' => 'whatsapp',
                'content' => "Hello {{customer_name}},\n\nYour shipment *{{tracking_number}}* for order *{{order_number}}* was successfully delivered and received by *{{recipient_name}}* at {{delivered_at}}.\n\nView digital receipt: {{receipt_url}}",
                'variables' => ['customer_name', 'order_number', 'tracking_number', 'recipient_name', 'delivered_at', 'receipt_url'],
            ],
            'delivery_exception' => [
                'name' => 'Delivery Exception Alert',
                'channel' => 'whatsapp',
                'content' => "Alert: Delivery attempt for shipment *{{tracking_number}}* encountered an issue: [{{exception_code}}] {{notes}}.\n\nOur logistics team is actively resolving this. Track status: {{tracking_url}}",
                'variables' => ['customer_name', 'tracking_number', 'exception_code', 'notes', 'tracking_url'],
            ],
        ];
    }
}
