<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\LoyaltyWallet\Models\Wishlist;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Wishlist::where('user_id', $user->id)
            ->with(['variant.product.brand', 'variant.product.primaryCategory'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $variant = $item->variant;
                $product = $variant?->product;

                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'created_at' => ($item->created_at ?? now())->toIso8601String(),
                    'variant' => $variant ? [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'name' => $variant->name,
                        'unit' => $variant->unit,
                        'pack_size' => $variant->pack_size,
                        'selling_price' => $variant->selling_price,
                        'mrp' => $variant->mrp,
                        'product' => $product ? [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'brand' => $product->brand?->name,
                            'category' => $product->primaryCategory?->name,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $items->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        $user = $request->user();

        $wishlistItem = Wishlist::firstOrCreate(
            [
                'user_id' => $user->id,
                'product_variant_id' => $validated['product_variant_id'],
            ],
            [
                'created_at' => now(),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $wishlistItem->id,
                'product_variant_id' => $wishlistItem->product_variant_id,
                'created_at' => ($wishlistItem->created_at ?? now())->toIso8601String(),
            ],
            'meta' => ['message' => 'Product saved to wishlist.'],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                    ->orWhere('product_variant_id', $id);
            })
            ->firstOrFail();

        $wishlistItem->delete();

        return response()->json([
            'meta' => ['message' => 'Item removed from wishlist.'],
        ]);
    }
}
