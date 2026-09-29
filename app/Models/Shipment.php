<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Packages\DriverLogistics\Models\Driver;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipment_number',
        'warehouse_id',
        'driver_id',
        'status',
        'delivery_otp',
        'carrier_or_driver_name',
        'driver_phone',
        'tracking_number',
        'dispatched_at',
        'delivered_at',
        'pod_recipient_name',
        'pod_signature',
        'pod_signature_data',
        'pod_otp',
        'pod_photo_path',
        'pod_latitude',
        'pod_longitude',
        'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'pod_latitude' => 'decimal:7',
        'pod_longitude' => 'decimal:7',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(DeliveryException::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isOutForDelivery(): bool
    {
        return $this->status === 'out_for_delivery';
    }
}
