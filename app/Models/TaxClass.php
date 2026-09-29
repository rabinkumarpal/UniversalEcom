<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate_percentage',
    ];

    protected function casts(): array
    {
        return [
            'rate_percentage' => 'float',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
