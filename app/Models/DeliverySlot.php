<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_zone_id',
        'name',
        'start_time',
        'end_time',
        'max_orders_per_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_orders_per_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
