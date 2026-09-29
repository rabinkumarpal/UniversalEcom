<?php

namespace Packages\VendorMarketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_name',
        'display_name',
        'slug',
        'email',
        'phone',
        'status',
        'approval_status',
        'commission_rate_percentage',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate_percentage' => 'float',
            'approved_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(VendorOffer::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(VendorInventory::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->approval_status === 'approved';
    }
}
