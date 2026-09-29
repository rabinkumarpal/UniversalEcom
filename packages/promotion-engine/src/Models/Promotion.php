<?php

namespace Packages\PromotionEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'type',
        'status',
        'priority',
        'stackable',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_limit_per_customer',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'stackable' => 'boolean',
            'priority' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'configuration' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PromotionRule::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(PromotionReward::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(PromotionBundleItem::class);
    }

    public function mixMatchItems(): HasMany
    {
        return $this->hasMany(PromotionMixMatchItem::class);
    }

    public function isValidNow(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usages()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
