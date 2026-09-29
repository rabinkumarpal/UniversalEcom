<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'tax_class_id',
        'sku',
        'barcode',
        'name',
        'unit',
        'pack_size',
        'weight_kg',
        'dimensions',
        'mrp',
        'selling_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'mrp' => 'integer',
            'selling_price' => 'integer',
            'weight_kg' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function quantityTiers(): HasMany
    {
        return $this->hasMany(QuantityPriceTier::class)->orderBy('min_quantity');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get total available stock across all warehouses.
     */
    public function getAvailableStockAttribute(): int
    {
        return (int) $this->inventoryItems()->sum('available');
    }

    /**
     * Resolve unit price for a given quantity according to quantity tiers.
     */
    public function getPriceForQuantity(int $quantity): int
    {
        $tier = $this->quantityTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $tier ? $tier->unit_price : $this->selling_price;
    }
}
