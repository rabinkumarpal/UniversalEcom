<?php

namespace Packages\WhatsAppNotifications\Models;

use App\Models\Order;
use App\Models\Shipment;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'recipient_phone',
        'recipient_name',
        'channel',
        'template_name',
        'parameters',
        'rendered_message',
        'gateway_message_id',
        'status',
        'order_id',
        'shipment_id',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function markDelivered(?DateTimeInterface $timestamp = null): self
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => $timestamp ?? now(),
        ]);

        return $this;
    }

    public function markRead(?DateTimeInterface $timestamp = null): self
    {
        $this->update([
            'status' => 'read',
            'read_at' => $timestamp ?? now(),
        ]);

        return $this;
    }

    public function markFailed(string $error): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        return $this;
    }
}
