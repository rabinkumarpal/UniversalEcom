<?php

namespace Packages\VendorMarketplace\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_variant_id',
        'vendor_sku',
        'vendor_price',
        'vendor_mrp',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vendor_price' => 'integer',
            'vendor_mrp' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
