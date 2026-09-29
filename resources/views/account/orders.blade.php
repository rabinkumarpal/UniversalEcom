@extends('account.layout')

@section('title', 'My Orders & Reorders')

@section('account_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Order History</h1>
        <p class="text-xs text-slate-500">Track delivery progress in real time, view invoices, write verified product reviews, or re-order frequent building supplies with one click.</p>
    </div>

    <div class="space-y-5">
        @forelse($orders as $order)
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs p-6 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="font-mono font-bold text-sm text-slate-900">#{{ $order->order_number }}</span>
                            @php
                                $statusColors = [
                                    'pending_payment' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'paid' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
                                    'confirmed' => 'bg-blue-50 text-blue-800 border-blue-200',
                                    'picking' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                    'packed' => 'bg-violet-50 text-violet-800 border-violet-200',
                                    'dispatched' => 'bg-purple-50 text-purple-800 border-purple-200',
                                    'out_for_delivery' => 'bg-teal-50 text-teal-800 border-teal-200',
                                    'delivered' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
                                ];
                                $badge = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full border {{ $badge }}">
                                {{ str_replace('_', ' ', $order->status) }}
                            </span>

                            @if($order->shipments->isNotEmpty())
                                @php $latestShipment = $order->shipments->sortByDesc('created_at')->first(); @endphp
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                    <span>Dispatch #{{ $latestShipment->shipment_number }} &bull; {{ str_replace('_', ' ', $latestShipment->status) }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 mt-1">Placed on {{ $order->created_at->format('M d, Y • h:i A') }}</div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Track Delivery Progress -->
                        <a href="{{ route('storefront.order_confirmation', $order->order_number) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            <span>Track Order</span>
                        </a>

                        <!-- 1-Click Reorder Action -->
                        <form action="{{ route('account.reorder', $order->order_number) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>1-Click Reorder</span>
                            </button>
                        </form>

                        <!-- Invoicing Links -->
                        @if($order->gstInvoice)
                            <a href="{{ route('account.invoices.show', $order->gstInvoice->invoice_number) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition inline-flex items-center gap-1 shadow-2xs">
                                <span>Tax Invoice &rarr;</span>
                            </a>
                        @else
                            <a href="{{ route('storefront.order_confirmation', $order->order_number) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition shadow-2xs">
                                <span>Receipt &rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Order Items Line Summary -->
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($order->items as $item)
                        @php
                            $product = $item->variant?->product;
                            $hasReviewed = $product && isset($userReviews[$product->id]);
                        @endphp
                        <div class="py-3" x-data="{ showReview: false, rating: 5 }">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    @if($product?->primaryMedia)
                                        <img src="{{ $product->primaryMedia->url }}" alt="{{ $item->product_name_snapshot }}" class="w-11 h-11 object-cover rounded-lg border border-slate-200 shrink-0">
                                    @else
                                        <div class="w-11 h-11 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 font-bold shrink-0 text-base">
                                            📦
                                        </div>
                                    @endif

                                    <div>
                                        @if($product)
                                            <a href="{{ route('storefront.product', $product->slug) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition inline-block">
                                                {{ $item->product_name_snapshot }}
                                            </a>
                                        @else
                                            <span class="font-bold text-slate-900">{{ $item->product_name_snapshot }}</span>
                                        @endif

                                        <div class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-2">
                                            <span>{{ $item->variant_name_snapshot }}</span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span class="font-mono text-slate-400">SKU: {{ $item->sku_snapshot }}</span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span class="text-slate-600 font-medium">Qty: {{ $item->quantity }}</span>
                                        </div>

                                        <!-- Review action triggers for delivered orders -->
                                        @if($order->status === 'delivered' && $product)
                                            <div class="mt-2">
                                                @if($hasReviewed)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                                        <svg class="w-3 h-3 text-emerald-600 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        <span>Reviewed ({{ $userReviews[$product->id] }} / 5)</span>
                                                    </span>
                                                @else
                                                    <button type="button" @click="showReview = !showReview" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-0.5 rounded-full transition shadow-2xs">
                                                        <svg class="w-3 h-3 text-amber-500 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        <span x-text="showReview ? 'Close Review Form' : 'Write a Product Review'">Write a Product Review</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="font-bold text-slate-900 text-sm whitespace-nowrap">
                                    ₹{{ number_format($item->line_total / 100, 2) }}
                                </div>
                            </div>

                            <!-- Expandable Verified Buyer Review Form -->
                            @if($order->status === 'delivered' && $product && !$hasReviewed)
                                <div x-show="showReview" x-cloak class="mt-3 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3 transition">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-slate-900">Verified Buyer Product Review for {{ $item->product_name_snapshot }}</h4>
                                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">✓ Verified Purchase</span>
                                    </div>

                                    <form action="{{ route('account.product.review', $product->id) }}" method="POST" class="space-y-3">
                                        @csrf
                                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                                        <input type="hidden" name="rating" :value="rating">

                                        <!-- Rating Selector -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Your Rating</label>
                                            <div class="flex items-center gap-1">
                                                <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                    <button type="button" @click="rating = star" class="p-0.5 focus:outline-none transition">
                                                        <svg class="w-5 h-5 transition" :class="star <= rating ? 'text-amber-400 fill-current' : 'text-slate-300 fill-current'" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    </button>
                                                </template>
                                                <span class="text-xs font-bold text-slate-600 ml-2" x-text="rating + ' / 5 stars'"></span>
                                            </div>
                                        </div>

                                        <!-- Headline -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Review Headline (optional)</label>
                                            <input type="text" name="title" placeholder="e.g. Great quality and fast site delivery"
                                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>

                                        <!-- Comment -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Review Experience <span class="text-rose-500">*</span></label>
                                            <textarea name="comment" rows="3" required placeholder="Share your experience with this material on your jobsite..."
                                                      class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center justify-end gap-2 pt-1">
                                            <button type="button" @click="showReview = false" class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition">
                                                Publish Review
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Footer Total -->
                <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline text-xs">
                    <span class="text-slate-500">Grand Total (Authoritative)</span>
                    <span class="text-base font-black text-slate-900">₹{{ number_format($order->grand_total / 100, 2) }}</span>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3">
                <span class="text-3xl block">📦</span>
                <h3 class="text-base font-bold text-slate-900">No orders found</h3>
                <p class="text-xs text-slate-500">You haven't placed any orders yet. Explore the catalog to make your first purchase.</p>
                <a href="{{ route('storefront.catalog') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700">Explore Catalog</a>
            </div>
        @endforelse

        @if($orders->hasPages())
            <div class="pt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
