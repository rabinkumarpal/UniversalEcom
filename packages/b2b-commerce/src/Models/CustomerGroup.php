<?php

namespace Packages\B2BCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'discount_percentage',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'float',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(ContractPriceList::class);
    }
}
