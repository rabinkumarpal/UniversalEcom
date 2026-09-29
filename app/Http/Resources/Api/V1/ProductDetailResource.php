<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'primary_category' => new CategoryResource($this->whenLoaded('primaryCategory')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'media' => $this->media->map(fn ($m) => [
                'url' => $m->url,
                'is_primary' => $m->is_primary,
            ]),
            'documents' => $this->documents->map(fn ($d) => [
                'title' => $d->title,
                'url' => $d->url,
                'file_type' => $d->file_type,
            ]),
            'variants' => ProductVariantResource::collection($this->variants),
            'attributes' => $this->attributeValues->map(fn ($av) => [
                'code' => $av->definition->code,
                'name' => $av->definition->name,
                'value' => $av->display_value,
            ]),
        ];
    }
}
