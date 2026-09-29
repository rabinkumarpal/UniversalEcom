<?php

namespace Packages\PromotionEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A required variant + quantity that must be in the cart to trigger a bundle promotion.
 */
class PromotionBundleItem extends Model
{
    protected $fillable = [
        'promotion_id',
        'product_variant_id',
        'required_quantity',
    ];

    protected function casts(): array
    {
        return [
            'required_quantity' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
