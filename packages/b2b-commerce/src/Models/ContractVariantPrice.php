<?php

namespace Packages\B2BCommerce\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVariantPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'custom_price',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'custom_price' => 'integer',
            'min_quantity' => 'integer',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(ContractPriceList::class, 'price_list_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
