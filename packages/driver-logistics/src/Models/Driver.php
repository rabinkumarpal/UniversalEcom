<?php

namespace Packages\DriverLogistics\Models;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'status',
        'current_latitude',
        'current_longitude',
        'last_active_at',
    ];

    protected $attributes = [
        'status' => 'active',
        'vehicle_type' => 'Flatbed Truck',
    ];

    protected function casts(): array
    {
        return [
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'last_active_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function activeShipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->whereIn('status', ['in_transit', 'out_for_delivery']);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active';
    }

    public function isOffDuty(): bool
    {
        return $this->status === 'off_duty';
    }

    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'current_latitude' => $lat,
            'current_longitude' => $lng,
            'last_active_at' => now(),
        ]);
    }
}
