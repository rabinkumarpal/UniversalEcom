@php
    $reviews = \Packages\LoyaltyWallet\Models\ProductReview::where('product_id', $product->id)
        ->approved()
        ->with('user')
        ->orderByDesc('created_at')
        ->get();
    $avgRating = $reviews->avg('rating') ?: 5.0;
    $totalReviews = $reviews->count();
@endphp

<div class="space-y-8 pt-8 border-t border-slate-200">
    <!-- Reviews Summary Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <span>Customer Reviews & Contractor Ratings</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    ★ {{ number_format($avgRating, 1) }} / 5.0
                </span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">Based on {{ $totalReviews }} verified contractor & builder evaluation(s)</p>
        </div>

        <button @click="showReviewForm = !showReviewForm" type="button" class="px-4 py-2 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition shadow-xs">
            Write a Review
        </button>
    </div>

    <!-- Review Submission Form (Collapsible via Alpine) -->
    <div x-show="showReviewForm" x-cloak class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Share Your Experience with this Material</h3>
        <form action="{{ route('account.product.review', $product->id) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quality Rating (1 to 5 Stars)</label>
                    <select name="rating" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold">
                        <option value="5">★★★★★ (5/5) — Exceptional Quality</option>
                        <option value="4">★★★★☆ (4/5) — Good Commercial Grade</option>
                        <option value="3">★★★☆☆ (3/5) — Average / Acceptable</option>
                        <option value="2">★★☆☆☆ (2/5) — Below Expectations</option>
                        <option value="1">★☆☆☆☆ (1/5) — Poor Quality</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Review Headline</label>
                    <input type="text" name="title" placeholder="e.g. Excellent bendability for RCC slab" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Detailed Technical Feedback & Site Performance</label>
                <textarea name="comment" rows="3" placeholder="Describe material consistency, packaging, workability, and durability..." class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs" required></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="showReviewForm = false" class="px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm">Submit Review</button>
            </div>
        </form>
    </div>

    <!-- Reviews List -->
    <div class="divide-y divide-slate-100">
        @forelse($reviews as $review)
            <div class="py-5 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-500 text-sm tracking-wider">
                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                        </span>
                        @if($review->title)
                            <span class="text-xs font-bold text-slate-900">{{ $review->title }}</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-400">{{ $review->created_at->format('M d, Y') }}</span>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">{{ $review->comment }}</p>

                <div class="flex items-center gap-2 pt-1 text-[11px]">
                    <span class="font-semibold text-slate-800">{{ $review->user?->name ?? 'Verified Buyer' }}</span>
                    @if($review->is_verified_buyer)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Verified Project Purchase
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-slate-400 text-xs">
                No customer reviews yet. Be the first contractor to share feedback for this material!
            </div>
        @endforelse
    </div>
</div>
