<?php

namespace Packages\WhatsAppNotifications\Services;

use Illuminate\Support\Str;

class WhatsAppGatewayClient
{
    protected bool $whatsappEnabled = true;

    public function __construct()
    {
        $this->whatsappEnabled = (bool) config('whatsapp_notifications.whatsapp_enabled', true);
    }

    public function setWhatsAppEnabled(bool $enabled): self
    {
        $this->whatsappEnabled = $enabled;

        return $this;
    }

    public function isWhatsAppEnabled(): bool
    {
        return $this->whatsappEnabled;
    }

    /**
     * Normalize phone numbers to standard E.164 format with Indian (+91) precedence.
     */
    public function normalizePhone(?string $phone): string
    {
        if (! $phone) {
            return '+910000000000';
        }

        // Strip non-numeric characters except leading plus
        $hasPlus = str_starts_with(trim($phone), '+');
        $digits = preg_replace('/[^\d]/', '', $phone);

        // Indian 10-digit standard mobile
        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        // 11 digits with leading 0 (e.g. 09876543210)
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+91'.substr($digits, 1);
        }

        // 12 digits starting with 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+'.$digits;
        }

        return $hasPlus ? '+'.$digits : '+'.$digits;
    }

    /**
     * Send simulated WhatsApp or fallback SMS message through the gateway.
     */
    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        $normalized = $this->normalizePhone($phone);
        $channel = $options['channel'] ?? ($this->whatsappEnabled ? 'whatsapp' : 'sms');

        // Prefix message IDs for traceability
        $prefix = $channel === 'whatsapp' ? 'WA-MSG-' : 'SMS-MSG-';
        $messageId = $prefix.strtoupper(Str::random(12));

        return [
            'success' => true,
            'channel' => $channel,
            'message_id' => $messageId,
            'recipient' => $normalized,
            'message' => $message,
            'sent_at' => now(),
        ];
    }
}
