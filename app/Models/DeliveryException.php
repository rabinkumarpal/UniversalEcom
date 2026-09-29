<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryException extends Model
{
    use HasFactory;

    public const CODE_CUSTOMER_UNAVAILABLE = 'CUSTOMER_UNAVAILABLE';

    public const CODE_WRONG_ADDRESS = 'WRONG_ADDRESS';

    public const CODE_PINCODE_NOT_SERVICEABLE = 'PINCODE_NOT_SERVICEABLE';

    public const CODE_STOCK_SHORTAGE = 'STOCK_SHORTAGE';

    public const CODE_VEHICLE_ISSUE = 'VEHICLE_ISSUE';

    protected $fillable = [
        'shipment_id',
        'exception_code',
        'notes',
        'recorded_by_user_id',
        'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
