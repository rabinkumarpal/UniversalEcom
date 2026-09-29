<?php

namespace App\Domain\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CatalogService
{
    /**
     * Search and filter products for storefront and API.
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'primaryCategory', 'variants.quantityTiers', 'primaryMedia'])
            ->where('status', 'published');

        // Search query
        if (! empty($filters['q'])) {
            $term = '%'.trim($filters['q']).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', $term));
            });
        }

        // Category filter
        if (! empty($filters['category'])) {
            $categorySlug = $filters['category'];
            $query->where(function (Builder $q) use ($categorySlug) {
                $q->whereHas('primaryCategory', fn ($cq) => $cq->where('slug', $categorySlug))
                    ->orWhereHas('categories', fn ($cq) => $cq->where('slug', $categorySlug));
            });
        }

        // Brand filter
        if (! empty($filters['brand'])) {
            $brandSlug = $filters['brand'];
            $query->whereHas('brand', fn ($bq) => $bq->where('slug', $brandSlug));
        }

        // Price filtering (on variants)
        if (isset($filters['price_min'])) {
            $min = (int) $filters['price_min'];
            $query->whereHas('variants', fn ($vq) => $vq->where('selling_price', '>=', $min));
        }
        if (isset($filters['price_max'])) {
            $max = (int) $filters['price_max'];
            $query->whereHas('variants', fn ($vq) => $vq->where('selling_price', '<=', $max));
        }

        // Attribute filter (e.g. attributes['grade'] = 'PPC' or attributes['size'] = 'XL')
        if (! empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attrCode => $attrValue) {
                $query->where(function (Builder $q) use ($attrCode, $attrValue) {
                    $q->whereHas('attributeValues', function ($avq) use ($attrCode, $attrValue) {
                        $avq->whereHas('definition', fn ($dq) => $dq->where('code', $attrCode))
                            ->where(function ($sub) use ($attrValue) {
                                $sub->where('value_text', $attrValue)
                                    ->orWhereHas('predefinedValue', fn ($pvq) => $pvq->where('value', $attrValue));
                            });
                    })->orWhereHas('variants.attributeValues', function ($vavq) use ($attrCode, $attrValue) {
                        $vavq->whereHas('definition', fn ($dq) => $dq->where('code', $attrCode))
                            ->where(function ($sub) use ($attrValue) {
                                $sub->where('value_text', $attrValue)
                                    ->orWhereHas('predefinedValue', fn ($pvq) => $pvq->where('value', $attrValue));
                            });
                    });
                });
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc' => $query->orderBy(
                Product::select('selling_price')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('selling_price', 'asc')
                    ->limit(1),
                'asc'
            ),
            'price_desc' => $query->orderBy(
                Product::select('selling_price')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('selling_price', 'desc')
                    ->limit(1),
                'desc'
            ),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Find a published product by slug with all required details.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::query()
            ->with([
                'brand',
                'primaryCategory',
                'categories',
                'variants.quantityTiers',
                'variants.attributeValues.definition',
                'variants.attributeValues.predefinedValue',
                'variants.taxClass',
                'variants.inventoryItems',
                'media',
                'documents',
                'attributeValues.definition',
                'attributeValues.predefinedValue',
            ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }
}
