<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_filterable',
        'is_variant_attribute',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_variant_attribute' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }
}
