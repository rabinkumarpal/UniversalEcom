<?php

namespace Packages\VendorMarketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'payout_number',
        'amount',
        'status',
        'period_start',
        'period_end',
        'paid_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
