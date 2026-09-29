@extends('layouts.admin')

@section('title', 'B2B Contract Price Lists — Admin')
@section('header_title', 'Negotiated Contract Price Lists')
@section('header_subtitle', 'Company-specific negotiated product prices, volume brackets, and group discounts')

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Contract Pricing Agreements</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Authoritative variant pricing overrides evaluated by the core Pricing Pipeline before public promotions.</p>
            </div>
            <a href="{{ route('admin.b2b.companies') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition">
                &larr; Companies &amp; Credit
            </a>
        </div>

        <!-- Create Contract Price List Accordion -->
        <div x-data="{ open: false }" class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
            <button @click="open = !open" class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span>+ Create Negotiated Contract Price List</span>
            </button>

            <form x-show="open" x-cloak action="{{ route('admin.b2b.price_lists.store') }}" method="POST" class="mt-4 p-5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Contract / Agreement Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Apex 2026 Bulk Cement Contract" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Associate with Company</label>
                    <select name="company_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Or Assign to Customer Group --</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->company_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Or Associate with Customer Group</label>
                    <select name="customer_group_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Or Assign to Specific Company --</option>
                        @foreach($customerGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Valid From</label>
                    <input type="date" name="valid_from" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Valid Until (Expiry)</label>
                    <input type="date" name="valid_until" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs">
                        Create Price Agreement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Price Lists Cards / Tables -->
    <div class="space-y-6">
        @forelse($priceLists as $list)
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ $list->name }}</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">Active</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Target: 
                            @if($list->company)
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $list->company->name }} ({{ $list->company->company_code }})</span>
                            @elseif($list->customerGroup)
                                <span class="font-bold text-purple-600 dark:text-purple-400">Group: {{ $list->customerGroup->name }}</span>
                            @else
                                <span class="text-slate-400">Universal B2B Agreement</span>
                            @endif
                            &bull; {{ $list->variant_prices_count }} Negotiated Item(s)
                        </p>
                    </div>

                    <!-- Add Variant Price Toggle -->
                    <div x-data="{ adding: false }">
                        <button @click="adding = !adding" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60 hover:bg-indigo-100 transition cursor-pointer">
                            + Add Negotiated SKU
                        </button>

                        <form x-show="adding" x-cloak action="{{ route('admin.b2b.price_lists.variant_price', $list->id) }}" method="POST" class="mt-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-wrap items-end gap-3 text-xs">
                            @csrf
                            <div class="flex-1 min-w-[200px]">
                                <label class="block font-semibold mb-1">Select Variant / SKU *</label>
                                <select name="product_variant_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white">
                                    @foreach($variants as $v)
                                        <option value="{{ $v->id }}">{{ $v->product?->name }} - {{ $v->name }} (SKU: {{ $v->sku }}) - Retail: ₹{{ number_format($v->price / 100, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="block font-semibold mb-1">Contract Price (₹) *</label>
                                <input type="number" step="0.01" min="0" name="custom_price_in_rupees" required placeholder="0.00" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white">
                            </div>
                            <div class="w-24">
                                <label class="block font-semibold mb-1">Min Qty *</label>
                                <input type="number" min="1" name="min_quantity" required value="1" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white">
                            </div>
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700">Save Price</button>
                            <button type="button" @click="adding = false" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-600">Cancel</button>
                        </form>
                    </div>
                </div>

                <!-- Variant Prices Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3">Product &amp; SKU</th>
                                <th class="p-3">Retail Price</th>
                                <th class="p-3">Contract Price</th>
                                <th class="p-3">Min Volume</th>
                                <th class="p-3 text-right">Savings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($list->variantPrices as $vp)
                                <tr>
                                    <td class="p-3">
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $vp->variant?->product?->name }} &bull; {{ $vp->variant?->name }}</p>
                                        <p class="font-mono text-slate-400 text-[11px]">SKU: {{ $vp->variant?->sku }}</p>
                                    </td>
                                    <td class="p-3 font-mono text-slate-500 line-through">
                                        ₹{{ number_format(($vp->variant?->price ?? 0) / 100, 2) }}
                                    </td>
                                    <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                        ₹{{ number_format($vp->custom_price / 100, 2) }}
                                    </td>
                                    <td class="p-3 font-mono">
                                        &ge; {{ $vp->min_quantity }} units
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        @php
                                            $diff = ($vp->variant?->price ?? 0) - $vp->custom_price;
                                            $savePct = ($vp->variant?->price ?? 0) > 0 ? round(($diff / $vp->variant->price) * 100, 1) : 0;
                                        @endphp
                                        -{{ $savePct }}% (₹{{ number_format($diff / 100, 2) }}/unit)
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-slate-400">No variant prices registered yet in this agreement.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400 shadow-xs">
                No contract price agreements created yet. Use the button above to create one.
            </div>
        @endforelse
    </div>
</div>
@endsection
