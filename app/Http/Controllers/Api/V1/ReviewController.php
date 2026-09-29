<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\LoyaltyWallet\Models\ProductReview;

class ReviewController extends Controller
{
    public function index(Request $request, string $productIdentifier): JsonResponse
    {
        $product = Product::where('id', $productIdentifier)
            ->orWhere('slug', $productIdentifier)
            ->firstOrFail();

        $reviews = ProductReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        $avgRating = ProductReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->avg('rating');

        $totalReviews = ProductReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->count();

        return response()->json([
            'data' => $reviews->items(),
            'meta' => [
                'average_rating' => $avgRating ? round($avgRating, 1) : 0,
                'total_reviews' => $totalReviews,
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, string $productIdentifier): JsonResponse
    {
        $product = Product::where('id', $productIdentifier)
            ->orWhere('slug', $productIdentifier)
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:150',
            'comment' => 'required|string|min:5|max:1500',
        ]);

        $user = $request->user();

        // Check verified buyer status: did customer purchase any variant of this product?
        $variantIds = $product->variants()->pluck('id');
        $hasPurchased = OrderItem::whereIn('product_variant_id', $variantIds)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['paid', 'confirmed', 'delivered', 'shipped', 'out_for_delivery']);
            })
            ->exists();

        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'is_verified_buyer' => $hasPurchased,
            'status' => 'approved',
        ]);

        return response()->json([
            'data' => $review,
            'meta' => [
                'message' => 'Review submitted successfully.',
                'is_verified_buyer' => $hasPurchased,
            ],
        ], 201);
    }
}
