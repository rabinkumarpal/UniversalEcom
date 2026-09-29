<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'unit' => $this->unit,
            'pack_size' => $this->pack_size,
            'weight_kg' => $this->weight_kg,
            'dimensions' => $this->dimensions,
            'mrp' => $this->mrp,
            'selling_price' => $this->selling_price,
            'available_stock' => $this->available_stock,
            'quantity_tiers' => $this->quantityTiers->map(fn ($t) => [
                'min_quantity' => $t->min_quantity,
                'max_quantity' => $t->max_quantity,
                'unit_price' => $t->unit_price,
            ]),
            'attributes' => $this->attributeValues->map(fn ($av) => [
                'code' => $av->definition->code,
                'name' => $av->definition->name,
                'value' => $av->display_value,
            ]),
        ];
    }
}
