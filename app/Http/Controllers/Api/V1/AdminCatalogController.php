<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCatalogController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'primary_category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|string|in:draft,published,archived',
            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.name' => 'required|string',
            'variants.*.mrp' => 'required|integer|min:0',
            'variants.*.selling_price' => 'required|integer|min:0',
            'variants.*.unit' => 'required|string',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'primary_category_id' => $validated['primary_category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'published_at' => ($validated['status'] === 'published') ? now() : null,
        ]);

        foreach ($validated['variants'] as $v) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $v['sku'],
                'name' => $v['name'],
                'mrp' => $v['mrp'],
                'selling_price' => $v['selling_price'],
                'unit' => $v['unit'],
                'status' => 'active',
            ]);
        }

        $this->audit->productCreated($product, $product->toArray());

        return response()->json([
            'data' => new ProductDetailResource($product->load(['variants', 'brand', 'primaryCategory'])),
            'meta' => ['message' => 'Product created successfully.'],
        ], 201);
    }
}
