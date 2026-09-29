<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'attribute_definition_id',
        'attribute_value_id',
        'value_text',
        'value_number',
        'value_boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    public function predefinedValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }

    public function getDisplayValueAttribute(): string
    {
        if ($this->predefinedValue) {
            return $this->predefinedValue->label ?? $this->predefinedValue->value;
        }

        if ($this->value_text !== null) {
            return $this->value_text;
        }

        if ($this->value_number !== null) {
            return (string) $this->value_number;
        }

        if ($this->value_boolean !== null) {
            return $this->value_boolean ? 'Yes' : 'No';
        }

        return '';
    }
}
