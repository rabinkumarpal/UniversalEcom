<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_fee',
        'min_order_free_shipping',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_fee' => 'integer',
            'min_order_free_shipping' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pincodes(): HasMany
    {
        return $this->hasMany(DeliveryZonePincode::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(DeliverySlot::class);
    }
}
