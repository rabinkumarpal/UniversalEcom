@extends('account.layout')

@section('title', 'Saved Wishlist')

@section('account_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Saved Wishlist</h1>
        <p class="text-xs text-slate-500">Bookmark essential materials and equipment for rapid purchasing during project milestones.</p>
    </div>

    @if($items->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3">
            <span class="text-3xl block">❤️</span>
            <h3 class="text-base font-bold text-slate-900">Your wishlist is empty</h3>
            <p class="text-xs text-slate-500">Save products while browsing the catalog to review or buy them later.</p>
            <a href="{{ route('storefront.catalog') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700">Browse Catalog</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($items as $wishlistItem)
                @php
                    $variant = $wishlistItem->variant;
                    $product = $variant?->product;
                @endphp
                @if($product && $variant)
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between p-5 space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 line-clamp-2">
                                        <a href="{{ route('storefront.product', $product->slug) }}" class="hover:text-indigo-600">{{ $product->name }}</a>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $variant->name }}</p>
                                </div>
                                <form action="{{ route('account.wishlist.toggle') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 p-1" title="Remove from Wishlist">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    </button>
                                </form>
                            </div>

                            <div class="font-mono text-[11px] text-slate-400">SKU: {{ $variant->sku }}</div>
                            <div class="text-base font-black text-slate-900">₹{{ number_format($variant->selling_price / 100, 2) }}</div>
                        </div>

                        <div>
                            <form action="{{ route('storefront.cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full py-2.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span>Move to Cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
