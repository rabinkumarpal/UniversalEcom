<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultVariant = $this->variants->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'primary_category' => new CategoryResource($this->whenLoaded('primaryCategory')),
            'primary_image' => $this->primaryMedia?->url ?? $this->media->first()?->url,
            'starting_price' => $defaultVariant?->selling_price,
            'mrp' => $defaultVariant?->mrp,
            'variants_count' => $this->variants->count(),
            'default_variant' => new ProductVariantResource($defaultVariant),
        ];
    }
}
