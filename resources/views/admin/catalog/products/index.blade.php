@extends('layouts.admin')

@section('title', 'Products')
@section('header_title', 'Product Catalog')
@section('header_subtitle', 'Create, edit, and manage all products and their variants')

@section('content')
<div x-data="productList()" class="space-y-5">

    {{-- Summary Metrics Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
            $totalProducts = $products->total();
            $publishedCount = \App\Models\Product::where('status', 'published')->count();
            $draftCount = \App\Models\Product::where('status', 'draft')->count();
            $archivedCount = \App\Models\Product::where('status', 'archived')->count();
        @endphp
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total</div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalProducts }}</div>
        </div>
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
            <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Published</div>
            <div class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-0.5">{{ $publishedCount }}</div>
        </div>
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Draft</div>
            <div class="text-xl font-black text-slate-600 dark:text-slate-300 mt-0.5">{{ $draftCount }}</div>
        </div>
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
            <div class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wide">Archived</div>
            <div class="text-xl font-black text-rose-700 dark:text-rose-300 mt-0.5">{{ $archivedCount }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
        <div class="flex flex-col lg:flex-row justify-between gap-3">
            <form method="GET" action="{{ route('admin.catalog.products.index') }}" class="flex flex-wrap items-center gap-2">
                {{-- Search --}}
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products…"
                        class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none w-44 placeholder:text-slate-400">
                </div>

                {{-- Status --}}
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    @foreach(['draft','published','archived'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>

                {{-- Category --}}
                <select name="category_id" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>

                {{-- Brand --}}
                <select name="brand_id" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>

                {{-- Stock Status --}}
                <select name="stock_status" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Stock</option>
                    <option value="in_stock" @selected(request('stock_status') === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected(request('stock_status') === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock')>Out of Stock</option>
                </select>

                @if(request('search') || request('status') || request('category_id') || request('brand_id') || request('stock_status'))
                    <a href="{{ route('admin.catalog.products.index') }}" class="px-2.5 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset
                    </a>
                @endif
                <button type="submit" class="px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700 dark:hover:bg-slate-600 transition font-medium">Search</button>
            </form>
            <a href="{{ route('admin.catalog.products.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors shadow-sm shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Product
            </a>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition.opacity
        class="sticky top-0 z-30 bg-indigo-600 dark:bg-indigo-700 rounded-xl px-4 py-3 shadow-lg flex flex-wrap items-center gap-3">
        <div class="text-white text-xs font-bold">
            <span x-text="selectedIds.length"></span> selected
        </div>
        <div class="h-4 w-px bg-indigo-400/40"></div>

        <form method="POST" action="{{ route('admin.catalog.products.bulk-action') }}" x-ref="bulkForm" class="flex flex-wrap items-center gap-2">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>

            {{-- Quick Actions --}}
            <button type="submit" name="action" value="publish"
                class="px-3 py-1.5 text-xs font-semibold bg-white/15 hover:bg-white/25 text-white rounded-lg transition">
                Publish
            </button>
            <button type="submit" name="action" value="draft"
                class="px-3 py-1.5 text-xs font-semibold bg-white/15 hover:bg-white/25 text-white rounded-lg transition">
                Move to Draft
            </button>
            <button type="submit" name="action" value="archive"
                class="px-3 py-1.5 text-xs font-semibold bg-white/15 hover:bg-white/25 text-white rounded-lg transition">
                Archive
            </button>

            {{-- Category Reassignment --}}
            <div class="flex items-center gap-1.5">
                <select name="category_id" class="bg-white/15 border border-white/20 rounded-lg px-2 py-1.5 text-xs text-white focus:ring-2 focus:ring-white/40 focus:outline-none [&>option]:text-slate-900">
                    <option value="">Assign Category…</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <button type="submit" name="action" value="assign_category"
                    class="px-2.5 py-1.5 text-xs font-semibold bg-white/15 hover:bg-white/25 text-white rounded-lg transition">
                    Assign
                </button>
            </div>

            <div class="h-4 w-px bg-indigo-400/40"></div>

            {{-- Delete (danger) --}}
            <button type="submit" name="action" value="delete"
                onclick="return confirm('Are you sure you want to delete ' + document.querySelectorAll('[name=\'product_ids[]\']:checked').length + ' products? Products with orders will be archived instead.')"
                class="px-3 py-1.5 text-xs font-semibold bg-rose-500/80 hover:bg-rose-500 text-white rounded-lg transition">
                Delete
            </button>
        </form>

        <button @click="selectedIds = []; $refs.selectAll.checked = false" class="ml-auto text-white/70 hover:text-white text-xs transition">
            Clear Selection
        </button>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Data Table --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" x-ref="selectAll" @change="toggleAll($event.target.checked)"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                        </th>
                        <th class="px-3 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Product</th>
                        <th class="px-3 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Category</th>
                        <th class="px-3 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Price Range</th>
                        <th class="px-3 py-3 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Stock</th>
                        <th class="px-3 py-3 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Variants</th>
                        <th class="px-3 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                        <th class="px-3 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Updated</th>
                        <th class="px-3 py-3 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($products as $product)
                    @php
                        // Calculate metrics
                        $variants = $product->variants;
                        $variantCount = $variants->count();
                        $prices = $variants->pluck('selling_price')->filter();
                        $minPrice = $prices->min();
                        $maxPrice = $prices->max();
                        $totalStock = $variants->sum(fn($v) => $v->inventoryItems->sum('available'));
                        $hasTiers = $variants->contains(fn($v) => $v->quantityTiers->isNotEmpty());

                        // Stock status badge
                        if ($totalStock === 0) {
                            $stockBadge = 'bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/50';
                            $stockLabel = 'Out of Stock';
                            $stockIcon = '○';
                        } elseif ($totalStock <= 10) {
                            $stockBadge = 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/50';
                            $stockLabel = 'Low Stock';
                            $stockIcon = '◐';
                        } else {
                            $stockBadge = 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50';
                            $stockLabel = 'In Stock';
                            $stockIcon = '●';
                        }

                        // Status badge
                        $statusBadge = match($product->status) {
                            'published' => 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                            'draft'     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            'archived'  => 'bg-rose-50 dark:bg-rose-900/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/50',
                            default     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-900/30 transition-colors group">
                        {{-- Checkbox --}}
                        <td class="px-3 py-3">
                            <input type="checkbox" value="{{ $product->id }}" x-model="selectedIds"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                        </td>

                        {{-- Product Info --}}
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($product->primaryMedia)
                                        <img src="{{ $product->primaryMedia->url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.catalog.products.edit', $product->id) }}" class="font-semibold text-slate-900 dark:text-white text-sm hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate block">{{ $product->name }}</a>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        @if($product->brand)
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $product->brand->name }}</span>
                                            <span class="text-slate-300 dark:text-slate-600">·</span>
                                        @endif
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono truncate">{{ $product->slug }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Category --}}
                        <td class="px-3 py-3">
                            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $product->primaryCategory->name ?? '—' }}</span>
                        </td>

                        {{-- Price Range --}}
                        <td class="px-3 py-3">
                            @if($prices->isNotEmpty())
                                <div class="text-xs font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                    @if($minPrice === $maxPrice)
                                        ₹{{ number_format($minPrice / 100, 2) }}
                                    @else
                                        ₹{{ number_format($minPrice / 100, 0) }} – ₹{{ number_format($maxPrice / 100, 0) }}
                                    @endif
                                </div>
                                @if($hasTiers)
                                    <span class="inline-flex items-center gap-0.5 mt-0.5 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide bg-violet-50 dark:bg-violet-900/40 text-violet-600 dark:text-violet-300 border border-violet-200 dark:border-violet-800/50 rounded">
                                        B2B Tiers
                                    </span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">No pricing</span>
                            @endif
                        </td>

                        {{-- Stock --}}
                        <td class="px-3 py-3 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-xs font-bold text-slate-900 dark:text-white">{{ number_format($totalStock) }}</span>
                                <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide border rounded {{ $stockBadge }}">
                                    {{ $stockLabel }}
                                </span>
                            </div>
                        </td>

                        {{-- Variants Count --}}
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ $variantCount }}</span>
                        </td>

                        {{-- Status --}}
                        <td class="px-3 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border {{ $statusBadge }}">{{ $product->status }}</span>
                        </td>

                        {{-- Updated --}}
                        <td class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $product->updated_at->format('d M Y') }}</td>

                        {{-- Actions --}}
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.catalog.products.edit', $product->id) }}" title="Edit"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.catalog.products.destroy', $product->id) }}" onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="text-sm text-slate-500 dark:text-slate-400">No products found.</p>
                                <a href="{{ route('admin.catalog.products.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Create the first product →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
        <div class="flex justify-center">{{ $products->links() }}</div>
    @endif

</div>

<script>
function productList() {
    return {
        selectedIds: [],
        toggleAll(checked) {
            if (checked) {
                this.selectedIds = @json($products->pluck('id')->map(fn($id) => (string) $id));
            } else {
                this.selectedIds = [];
            }
        }
    };
}
</script>
@endsection
