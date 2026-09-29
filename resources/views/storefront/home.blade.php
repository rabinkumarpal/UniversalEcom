@extends('layouts.storefront')

@section('title', 'Universal Ecommerce — Construction Materials Reference Store')

@section('content')
<div class="space-y-12">
    <!-- Hero Banner -->
    <section class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 bg-indigo-500/20 text-indigo-300 px-3 py-1 rounded-full text-xs font-semibold border border-indigo-400/30">
                    <span>🏗️ First Reference Vertical</span>
                    <span>•</span>
                    <span class="text-white">Construction & Industrial Supplies</span>
                </div>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight">
                    Direct Site Delivery & Bulk Pricing for Builders
                </h1>
                <p class="text-base sm:text-lg text-slate-300 max-w-2xl leading-relaxed">
                    Order BIS-certified cement, primary steel rebars, plumbing, paints, and plywood with instant tiered volume discounts, scheduled slot delivery, and pay on delivery.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="{{ route('storefront.catalog') }}" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        Browse Materials Catalog &rarr;
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="px-6 py-3.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-xl border border-slate-700 transition">
                        View Admin Dashboard
                    </a>
                </div>

                <!-- Live Feature Badges -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-800/80">
                    <div>
                        <div class="text-xl font-bold text-white">Tiered Rates</div>
                        <div class="text-xs text-slate-400">Save up to ₹30/bag on 50+ qty</div>
                    </div>
                    <div>
                        <div class="text-xl font-bold text-white">Direct Dispatch</div>
                        <div class="text-xs text-slate-400">Warehouse row-locking safety</div>
                    </div>
                    <div>
                        <div class="text-xl font-bold text-white">Multi-Vendor</div>
                        <div class="text-xs text-slate-400">Automatic order splitting</div>
                    </div>
                </div>
            </div>

            <!-- Promotion Highlight Box -->
            <div class="lg:col-span-5 bg-white/10 backdrop-blur-md p-6 sm:p-8 rounded-2xl border border-white/15 space-y-4">
                <span class="px-2.5 py-1 bg-emerald-500 text-white text-[11px] font-bold rounded-md uppercase tracking-wider">Active Promotional Deal</span>
                <h3 class="text-2xl font-bold text-white">Spend ₹50,000 & Unlock Free Crane Site Unloading</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Powered by the portable <strong>Promotion & Discount Engine</strong>. Meets the commercial criteria with server-authoritative discount evaluation.
                </p>
                <div class="bg-black/30 p-4 rounded-xl space-y-2 border border-white/10">
                    <div class="flex justify-between text-xs font-semibold">
                        <span>RCC Slab Cast Campaign</span>
                        <span class="text-emerald-400">5% Instant Discount</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Applies to full truckload cement orders automatically at checkout.
                    </div>
                </div>
                <a href="{{ route('storefront.catalog', ['category' => 'cement']) }}" class="block text-center py-2.5 bg-white text-slate-900 font-bold text-sm rounded-lg hover:bg-slate-100 transition">
                    Shop Cement Volume Offers &rarr;
                </a>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <!-- Categories Grid -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Material Categories</h2>
                    <p class="text-xs text-slate-500">Structured hierarchical catalog with dynamic attribute facets</p>
                </div>
                <a href="{{ route('storefront.catalog') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($categories as $cat)
                    <a href="{{ route('storefront.catalog', ['category' => $cat->slug]) }}" class="group bg-white p-5 rounded-2xl border border-slate-200 hover:border-indigo-400 shadow-xs hover:shadow-md transition flex flex-col items-center text-center">
                        <div class="w-14 h-14 rounded-xl bg-slate-100 group-hover:bg-indigo-50 flex items-center justify-center text-2xl mb-3 transition">
                            @if(str_contains(strtolower($cat->name), 'civil')) 🧱
                            @elseif(str_contains(strtolower($cat->name), 'plumbing')) 🚰
                            @elseif(str_contains(strtolower($cat->name), 'paint')) 🎨
                            @elseif(str_contains(strtolower($cat->name), 'electric')) ⚡
                            @else 🛠️ @endif
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition">{{ $cat->name }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $cat->children->count() }} subcategories</p>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Featured Products with Tiered Quantity Preview -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Featured Construction Materials</h2>
                    <p class="text-xs text-slate-500">Real-time inventory from regional warehouses with bulk quantity brackets</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                    @php $defVar = $product->variants->first(); @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col justify-between group">
                        <div>
                            <!-- Product Image -->
                            <div class="relative h-48 bg-slate-100 overflow-hidden">
                                @if($product->primaryMedia)
                                    <img src="{{ $product->primaryMedia->url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-400 font-medium text-xs">No Image</div>
                                @endif
                                <div class="absolute top-2.5 left-2.5 bg-white/90 backdrop-blur-xs px-2 py-0.5 rounded text-[10px] font-bold text-slate-800 border border-slate-200">
                                    {{ $product->brand?->name ?? 'Industrial' }}
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-5 space-y-2">
                                <span class="text-[11px] font-semibold text-indigo-600 block uppercase tracking-wide">
                                    {{ $product->primaryCategory->name }}
                                </span>
                                <h3 class="text-sm font-bold text-slate-900 line-clamp-2 hover:text-indigo-600">
                                    <a href="{{ route('storefront.product', $product->slug) }}">{{ $product->name }}</a>
                                </h3>

                                <!-- Pricing Display -->
                                @if($defVar)
                                    <div class="pt-2">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-lg font-black text-slate-900">₹{{ number_format($defVar->selling_price / 100, 2) }}</span>
                                            @if($defVar->mrp > $defVar->selling_price)
                                                <span class="text-xs text-slate-400 line-through">₹{{ number_format($defVar->mrp / 100, 2) }}</span>
                                            @endif
                                            <span class="text-[11px] text-slate-500">/ {{ $defVar->unit }}</span>
                                        </div>

                                        <!-- Quantity Tier Pill -->
                                        @if($defVar->quantityTiers->isNotEmpty())
                                            <div class="mt-2 bg-amber-50 text-amber-900 text-[10px] font-bold px-2 py-1 rounded border border-amber-200 inline-block">
                                                Bulk: ₹{{ number_format($defVar->quantityTiers->last()->unit_price / 100, 2) }} on {{ $defVar->quantityTiers->last()->min_quantity }}+ {{ $defVar->unit }}s
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="p-5 pt-0">
                            <form action="{{ route('storefront.cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="variant_id" value="{{ $defVar->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full py-2.5 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add to Cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
