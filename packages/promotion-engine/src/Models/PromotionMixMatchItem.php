<?php

namespace Packages\PromotionEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A variant eligible to count toward the "Choose any N from this pool" mix & match promotion.
 */
class PromotionMixMatchItem extends Model
{
    protected $fillable = [
        'promotion_id',
        'product_variant_id',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
