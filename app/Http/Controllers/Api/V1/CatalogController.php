<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\CatalogService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService
    ) {}

    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->with(['children' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function category(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->with(['children', 'parent'])
            ->firstOrFail();

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    public function brands(): AnonymousResourceCollection
    {
        $brands = Brand::where('status', 'active')->orderBy('name')->get();

        return BrandResource::collection($brands);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = $this->catalogService->search($request->all(), (int) $request->input('per_page', 15));

        return ProductResource::collection($products);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $products = $this->catalogService->search(['q' => $request->input('q')], 20);

        return ProductResource::collection($products);
    }

    public function product(string $slug): JsonResponse
    {
        $product = $this->catalogService->findBySlug($slug);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json([
            'data' => new ProductDetailResource($product),
        ]);
    }
}
