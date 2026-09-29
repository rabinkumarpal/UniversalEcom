@extends('layouts.storefront')

@section('title', 'Materials Catalog — Universal Ecommerce Platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-baseline justify-between border-b border-slate-200 pb-6 mb-8">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Materials Catalog</h1>
            <p class="text-xs text-slate-500 mt-1">Showing {{ $products->total() }} commercial physical products</p>
        </div>

        <!-- Sort dropdown -->
        <form method="GET" action="{{ route('storefront.catalog') }}" class="flex items-center gap-2">
            @foreach(request()->except(['sort', 'page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <label class="text-xs font-semibold text-slate-600">Sort By:</label>
            <select name="sort" onchange="this.form.submit()" class="text-xs font-medium border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white">
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Filter Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <form method="GET" action="{{ route('storefront.catalog') }}" class="bg-white p-5 rounded-2xl border border-slate-200 space-y-6 shadow-xs">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif

                <!-- Category Filter -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Categories</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                            <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>All Categories</span>
                        </label>
                        @foreach($categories as $cat)
                            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="font-medium">{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Brand Filter -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Brands</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                            <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>All Brands</span>
                        </label>
                        @foreach($brands as $brand)
                            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                <input type="radio" name="brand" value="{{ $brand->slug }}" {{ request('brand') == $brand->slug ? 'checked' : '' }} onchange="this.form.submit()">
                                <span>{{ $brand->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2 bg-indigo-600 text-white font-bold text-xs rounded-lg hover:bg-indigo-700 transition">Apply Filters</button>
                    @if(request()->hasAny(['category', 'brand', 'q', 'price_min', 'price_max']))
                        <a href="{{ route('storefront.catalog') }}" class="block text-center mt-2 text-xs text-slate-500 underline">Clear All</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Products Listing Grid -->
        <div class="lg:col-span-3">
            @if($products->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                    <p class="text-slate-500 text-sm">No products found matching your search filters.</p>
                    <a href="{{ route('storefront.catalog') }}" class="mt-3 inline-block text-xs font-bold text-indigo-600 underline">Reset Search Filters</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        @php $defVar = $product->variants->first(); @endphp
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col justify-between group">
                            <div>
                                <div class="relative h-44 bg-slate-100 overflow-hidden">
                                    @if($product->primaryMedia)
                                        <img src="{{ $product->primaryMedia->url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-400 font-medium text-xs">No Image</div>
                                    @endif
                                    <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-xs px-2 py-0.5 rounded text-[10px] font-bold text-slate-800 border border-slate-200">
                                        {{ $product->brand?->name ?? 'Industrial' }}
                                    </div>
                                    @if($product->media->count() > 1)
                                        <div class="absolute bottom-2 right-2 bg-slate-900/70 backdrop-blur-xs text-white text-[9px] font-bold px-1.5 py-0.5 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>{{ $product->media->count() }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="p-4 space-y-1.5">
                                    <span class="text-[10px] font-semibold text-indigo-600 block uppercase tracking-wide">
                                        {{ $product->primaryCategory->name }}
                                    </span>
                                    <h3 class="text-xs font-bold text-slate-900 line-clamp-2 hover:text-indigo-600">
                                        <a href="{{ route('storefront.product', $product->slug) }}">{{ $product->name }}</a>
                                    </h3>

                                    @if($defVar)
                                        <div class="pt-1.5">
                                            <div class="flex items-baseline gap-1.5">
                                                <span class="text-base font-black text-slate-900">₹{{ number_format($defVar->selling_price / 100, 2) }}</span>
                                                <span class="text-[10px] text-slate-500">/ {{ $defVar->unit }}</span>
                                            </div>

                                            @if($defVar->quantityTiers->isNotEmpty())
                                                <div class="mt-1.5 bg-amber-50 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded border border-amber-200 inline-block">
                                                    Bulk: ₹{{ number_format($defVar->quantityTiers->last()->unit_price / 100, 2) }} on {{ $defVar->quantityTiers->last()->min_quantity }}+ {{ $defVar->unit }}s
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="p-4 pt-0">
                                <form action="{{ route('storefront.cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $defVar->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl transition">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
