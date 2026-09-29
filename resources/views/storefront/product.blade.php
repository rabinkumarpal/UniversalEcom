@extends('layouts.storefront')

@section('title', $product->name . ' — Universal Ecommerce Platform')

@section('content')
@php
    $activeVariants = $product->variants->where('status', 'active');
    $selectedVariant = $activeVariants->first() ?? $product->variants->first();
    $variantsData = ($activeVariants->isNotEmpty() ? $activeVariants : $product->variants)->map(function($v) {
        return [
            'id' => $v->id,
            'name' => $v->name,
            'sku' => $v->sku,
            'unit' => $v->unit,
            'pack_size' => $v->pack_size,
            'weight_kg' => $v->weight_kg ? (float) $v->weight_kg : null,
            'barcode' => $v->barcode,
            'tax_name' => $v->taxClass?->name ?? 'GST',
            'tax_rate' => $v->taxClass ? (float) $v->taxClass->rate_percentage : 18.0,
            'mrp' => $v->mrp / 100,
            'selling_price' => $v->selling_price / 100,
            'savings_pct' => $v->mrp > $v->selling_price ? round((($v->mrp - $v->selling_price) / $v->mrp) * 100) : 0,
            'stock' => $v->available_stock,
            'warehouse' => $v->inventoryItems->first()?->warehouse?->name ?? 'Central Distribution Hub',
            'tiers' => $v->quantityTiers->map(fn($t) => [
                'min' => (int) $t->min_quantity,
                'max' => $t->max_quantity ? (int) $t->max_quantity : null,
                'price' => $t->unit_price / 100,
            ])->values(),
        ];
    })->values();
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{
    variants: {{ Js::from($variantsData) }},
    selectedVariantId: {{ $selectedVariant?->id ?? 0 }},
    selectedPrice: {{ ($selectedVariant?->selling_price ?? 0) / 100 }},
    selectedMrp: {{ ($selectedVariant?->mrp ?? 0) / 100 }},
    selectedUnit: '{{ $selectedVariant?->unit ?? 'unit' }}',
    selectedPackSize: '{{ $selectedVariant?->pack_size ?? '' }}',
    selectedWeight: {{ $selectedVariant?->weight_kg ?? 'null' }},
    selectedSku: '{{ $selectedVariant?->sku ?? '' }}',
    selectedStock: {{ $selectedVariant?->available_stock ?? 0 }},
    selectedWarehouse: '{{ $selectedVariant?->inventoryItems->first()?->warehouse?->name ?? 'Central Distribution Hub' }}',
    selectedTaxName: '{{ $selectedVariant?->taxClass?->name ?? 'GST' }}',
    selectedTaxRate: {{ $selectedVariant?->taxClass?->rate_percentage ?? 18 }},
    selectedTiers: {{ Js::from($variantsData->firstWhere('id', $selectedVariant?->id)['tiers'] ?? []) }},
    qty: 1,
    activeImage: '{{ $product->primaryMedia?->url ?? ($product->media->first()?->url ?? '') }}',
    activeTab: 'specs',
    showReviewForm: false,
    get effectiveUnitPrice() {
        let price = this.selectedPrice;
        if (this.selectedTiers && this.selectedTiers.length) {
            for (const t of this.selectedTiers) {
                if (this.qty >= t.min && (!t.max || this.qty <= t.max)) {
                    price = t.price;
                }
            }
        }
        return price;
    },
    get subtotal() {
        return this.effectiveUnitPrice * this.qty;
    },
    get totalSavings() {
        if (this.selectedMrp > this.effectiveUnitPrice) {
            return (this.selectedMrp - this.effectiveUnitPrice) * this.qty;
        }
        return 0;
    },
    get savingsPercentage() {
        if (this.selectedMrp > 0 && this.effectiveUnitPrice < this.selectedMrp) {
            return Math.round(((this.selectedMrp - this.effectiveUnitPrice) / this.selectedMrp) * 100);
        }
        return 0;
    },
    get activeTierMin() {
        let activeMin = 0;
        if (this.selectedTiers && this.selectedTiers.length) {
            for (const t of this.selectedTiers) {
                if (this.qty >= t.min && (!t.max || this.qty <= t.max)) {
                    activeMin = t.min;
                }
            }
        }
        return activeMin;
    },
    selectVariant(v) {
        this.selectedVariantId = v.id;
        this.selectedPrice = v.selling_price;
        this.selectedMrp = v.mrp;
        this.selectedUnit = v.unit;
        this.selectedPackSize = v.pack_size;
        this.selectedWeight = v.weight_kg;
        this.selectedSku = v.sku;
        this.selectedStock = v.stock;
        this.selectedWarehouse = v.warehouse || 'Central Distribution Hub';
        this.selectedTaxName = v.tax_name || 'GST';
        this.selectedTaxRate = v.tax_rate || 18;
        this.selectedTiers = v.tiers || [];
    },
    updateQty(val) {
        this.qty = Math.max(1, parseInt(val) || 1);
    }
}">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2 items-center flex-wrap">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600 transition">Home</a>
        <span class="text-slate-300">/</span>
        @if($product->primaryCategory)
            <a href="{{ route('storefront.catalog', ['category' => $product->primaryCategory->slug]) }}" class="hover:text-indigo-600 transition">
                {{ $product->primaryCategory->name }}
            </a>
            <span class="text-slate-300">/</span>
        @endif
        <span class="text-slate-800 font-semibold truncate">{{ $product->name }}</span>
    </nav>

    <!-- Top Product Showcase (Balanced 2-Column Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

        <!-- Left Column: Product Imagery & Trust Features (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            {{-- Main Image Canvas --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs aspect-square w-full flex items-center justify-center p-6 relative overflow-hidden group">
                @if($product->media->isNotEmpty() || $product->primaryMedia)
                    <img :src="activeImage"
                        src="{{ $product->primaryMedia?->url ?? ($product->media->first()?->url ?? '') }}"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">

                    @if($product->media->count() > 1)
                        <div class="absolute bottom-3 right-3 px-2.5 py-1 rounded-full bg-slate-900/80 text-white text-[11px] font-medium backdrop-blur-xs flex items-center gap-1.5 shadow-xs pointer-events-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ $product->media->count() }} Photos</span>
                        </div>
                    @endif
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                        <svg class="w-16 h-16 stroke-current mb-2" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-medium text-slate-400">No Image Available</span>
                    </div>
                @endif
            </div>

            {{-- Thumbnails Carousel --}}
            @if($product->media->count() > 1)
                <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
                    @foreach($product->media as $media)
                        <button type="button"
                            @click="activeImage = '{{ $media->url }}'"
                            @mouseenter="activeImage = '{{ $media->url }}'"
                            :class="activeImage === '{{ $media->url }}' ? 'ring-2 ring-indigo-600 border-indigo-600' : 'border-slate-200 hover:border-slate-300 opacity-70 hover:opacity-100'"
                            class="relative w-16 h-16 rounded-xl overflow-hidden border bg-white shrink-0 transition focus:outline-none cursor-pointer p-1">
                            <img src="{{ $media->url }}" alt="{{ $product->name }}" class="w-full h-full object-contain rounded-lg">
                            @if($media->is_primary)
                                <span class="absolute top-1 left-1 px-1 py-0.2 rounded bg-indigo-600 text-white text-[7px] font-black uppercase">
                                    Main
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Trust Badges Row --}}
            <div class="grid grid-cols-3 gap-2.5 pt-2">
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                    <svg class="w-5 h-5 text-indigo-600 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-[11px] font-bold text-slate-800 block">100% Genuine</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Factory Sealed</span>
                </div>
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                    <svg class="w-5 h-5 text-indigo-600 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-[11px] font-bold text-slate-800 block">Input Tax Credit</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Full GST Invoice</span>
                </div>
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                    <svg class="w-5 h-5 text-indigo-600 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    <span class="text-[11px] font-bold text-slate-800 block">Bulk Logistics</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Heavy Fleet Trucks</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Buy Box, Pricing & Variants (7 cols) -->
        <div class="lg:col-span-7 space-y-5">
            {{-- Header Details --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-100">
                        {{ $product->brand?->name ?? 'Industrial Grade' }}
                    </span>
                    @if($product->primaryCategory)
                        <span class="text-xs text-slate-300">•</span>
                        <a href="{{ route('storefront.catalog', ['category' => $product->primaryCategory->slug]) }}"
                            class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition">
                            {{ $product->primaryCategory->name }}
                        </a>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $product->name }}
                </h1>

                @if($product->short_description)
                    <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed">
                        {{ $product->short_description }}
                    </p>
                @endif
            </div>

            <!-- Variant Selector (if multiple active variants exist) -->
            @if($product->variants->where('status', 'active')->count() > 1)
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Select Packaging / Pack Size:
                        </label>
                        <span class="text-xs text-indigo-600 font-semibold">{{ $product->variants->where('status', 'active')->count() }} options available</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        @foreach($product->variants->where('status', 'active') as $v)
                            <button type="button"
                                @click="selectVariant({{ Js::from([
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'sku' => $v->sku,
                                    'unit' => $v->unit,
                                    'pack_size' => $v->pack_size,
                                    'weight_kg' => $v->weight_kg ? (float) $v->weight_kg : null,
                                    'barcode' => $v->barcode,
                                    'tax_name' => $v->taxClass?->name ?? 'GST',
                                    'tax_rate' => $v->taxClass ? (float) $v->taxClass->rate_percentage : 18.0,
                                    'mrp' => $v->mrp / 100,
                                    'selling_price' => $v->selling_price / 100,
                                    'savings_pct' => $v->mrp > $v->selling_price ? round((($v->mrp - $v->selling_price) / $v->mrp) * 100) : 0,
                                    'stock' => $v->available_stock,
                                    'warehouse' => $v->inventoryItems->first()?->warehouse?->name ?? 'Central Distribution Hub',
                                    'tiers' => $v->quantityTiers->map(fn($t) => ['min' => (int) $t->min_quantity, 'max' => $t->max_quantity ? (int) $t->max_quantity : null, 'price' => $t->unit_price / 100])->values(),
                                ]) }})"
                                :class="selectedVariantId === {{ $v->id }}
                                    ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20 text-indigo-950 font-bold shadow-xs'
                                    : 'border-slate-200 bg-white hover:border-slate-300 text-slate-700 font-medium'"
                                class="p-3 rounded-xl border text-left transition duration-150 relative focus:outline-none cursor-pointer">
                                <div class="text-xs font-bold truncate">{{ $v->name }}</div>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-sm font-black text-slate-900 font-mono">₹{{ number_format($v->selling_price / 100, 2) }}</span>
                                    <span class="text-[10px] text-slate-400">/{{ $v->unit }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[10px]">
                                    <span class="font-mono text-slate-400">{{ $v->sku }}</span>
                                    @if($v->mrp > $v->selling_price)
                                        <span class="font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.2 rounded">
                                            -{{ round((($v->mrp - $v->selling_price) / $v->mrp) * 100) }}%
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Clean Buy Box Card -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 space-y-4 shadow-xs">
                {{-- Price Block --}}
                <div class="space-y-1.5">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <span class="text-3xl sm:text-4xl font-black text-slate-900 font-mono tracking-tight" x-text="'₹' + selectedPrice.toFixed(2)">
                            ₹{{ number_format(($selectedVariant?->selling_price ?? 0) / 100, 2) }}
                        </span>
                        <template x-if="selectedMrp > selectedPrice">
                            <span class="text-base text-slate-400 line-through font-mono" x-text="'₹' + selectedMrp.toFixed(2)">
                                ₹{{ number_format(($selectedVariant?->mrp ?? 0) / 100, 2) }}
                            </span>
                        </template>
                        <span class="text-xs text-slate-500 font-medium">per <span x-text="selectedUnit">{{ $selectedVariant?->unit ?? 'unit' }}</span></span>

                        <template x-if="savingsPercentage > 0">
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                Save <span x-text="savingsPercentage"></span>%
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Inclusive of <strong x-text="selectedTaxRate + '% GST'"></strong> (Tax Invoice with ITC)</span>
                        </span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-500 font-mono text-[11px]">SKU: <strong class="text-slate-800" x-text="selectedSku">{{ $selectedVariant?->sku }}</strong></span>
                        <template x-if="selectedWeight">
                            <span class="text-slate-500 text-[11px]">
                                • Weight: <strong class="text-slate-800" x-text="selectedWeight + ' KG'"></strong>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Fulfillment Status Banner --}}
                <div class="bg-slate-50 px-3.5 py-2.5 rounded-xl border border-slate-200/80 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-slate-700 font-medium">
                            In Stock: <strong class="text-slate-900" x-text="selectedStock + ' ' + selectedUnit + 's'"></strong> available
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">
                        Dispatch via <span class="text-indigo-600 font-semibold" x-text="selectedWarehouse"></span>
                    </span>
                </div>

                {{-- Volume Pricing Tier Card (Clean & High-Contrast) --}}
                <div class="pt-3 border-t border-slate-100 space-y-2.5" x-show="selectedTiers && selectedTiers.length" x-cloak>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">
                            Direct Volume Pricing Tiers:
                        </span>
                        <span class="text-[10px] text-indigo-600 font-medium">Automated bulk checkout discount</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        {{-- Base Tier --}}
                        <div class="p-2.5 rounded-xl border text-center transition"
                             :class="activeTierMin === 0 ? 'bg-indigo-50/70 border-indigo-400 ring-1 ring-indigo-400' : 'bg-slate-50 border-slate-200'">
                            <div class="text-[10px] text-slate-500 font-medium">
                                1 – <span x-text="(selectedTiers[0]?.min - 1) || 9"></span> <span x-text="selectedUnit"></span>s
                            </div>
                            <div class="text-xs font-black text-slate-900 font-mono mt-0.5" x-text="'₹' + selectedPrice.toFixed(2)"></div>
                            <div class="text-[9px] text-slate-400 mt-0.5">Standard Rate</div>
                        </div>

                        {{-- Volume Tiers from Database --}}
                        <template x-for="tier in selectedTiers" :key="tier.min">
                            <div class="p-2.5 rounded-xl border text-center transition"
                                 :class="activeTierMin === tier.min
                                    ? 'bg-emerald-50/80 border-emerald-500 ring-1 ring-emerald-500 text-emerald-950 shadow-2xs'
                                    : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <div class="text-[10px] font-bold" :class="activeTierMin === tier.min ? 'text-emerald-900' : 'text-slate-500'">
                                    <span x-text="tier.min + (tier.max ? ' – ' + tier.max : '+')"></span> <span x-text="selectedUnit"></span>s
                                </div>
                                <div class="text-xs font-black font-mono mt-0.5" :class="activeTierMin === tier.min ? 'text-emerald-700' : 'text-slate-900'"
                                     x-text="'₹' + tier.price.toFixed(2)"></div>
                                <div class="text-[9px] font-bold uppercase mt-0.5"
                                     :class="activeTierMin === tier.min ? 'text-emerald-700' : 'text-indigo-600'"
                                     x-text="activeTierMin === tier.min ? '✓ APPLIED RATE' : Math.round(((selectedPrice - tier.price) / selectedPrice) * 100) + '% OFF'"></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Add to Cart Form --}}
                <form action="{{ route('storefront.cart.add') }}" method="POST" class="pt-3 border-t border-slate-100 space-y-3.5">
                    @csrf
                    <input type="hidden" name="variant_id" :value="selectedVariantId">

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Order Quantity (<span x-text="selectedUnit"></span>s)</label>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center border border-slate-300 rounded-xl bg-white overflow-hidden shadow-2xs shrink-0">
                                <button type="button" @click="updateQty(qty - 1)" class="px-3.5 py-2 text-slate-600 hover:bg-slate-100 font-bold text-sm select-none transition">-</button>
                                <input type="number" name="quantity" x-model.number="qty" @input="updateQty($event.target.value)" min="1"
                                    class="w-14 text-center text-xs font-bold border-0 focus:ring-0 text-slate-900 font-mono py-2">
                                <button type="button" @click="updateQty(qty + 1)" class="px-3.5 py-2 text-slate-600 hover:bg-slate-100 font-bold text-sm select-none transition">+</button>
                            </div>

                            <div class="flex-1 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200/80 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 font-medium">Estimated Line Total:</span>
                                    <span class="text-sm font-black text-indigo-700 font-mono" x-text="'₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center justify-between mt-0.5">
                                    <span>Rate: ₹<span x-text="effectiveUnitPrice.toFixed(2)"></span> / <span x-text="selectedUnit"></span></span>
                                    <span class="text-emerald-700 font-bold" x-show="totalSavings > 0">
                                        You save ₹<span x-text="totalSavings.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>!
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                        <button type="submit" class="flex-1 py-3.5 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs hover:shadow-indigo-500/20 transition flex items-center justify-center gap-2 cursor-pointer text-xs uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>Add to Order Cart</span>
                        </button>
                    </div>
                </form>

                <form action="{{ route('account.wishlist.toggle') }}" method="POST">
                    @csrf
                    <input type="hidden" name="variant_id" :value="selectedVariantId">
                    <button type="submit" class="w-full py-2 px-3 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 font-medium text-xs rounded-xl border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-rose-500 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span>Save to Project Wishlist</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Full-Width Tabbed Details Section (Below the Fold) -->
    <div class="mt-12 bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        {{-- Tab Bar --}}
        <div class="flex items-center gap-1 border-b border-slate-200 bg-slate-50/60 px-4 pt-2 overflow-x-auto scrollbar-thin">
            <button type="button" @click="activeTab = 'specs'"
                :class="activeTab === 'specs' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium'"
                class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition whitespace-nowrap flex items-center gap-2">
                <span>📋 Technical Specifications</span>
            </button>

            <button type="button" @click="activeTab = 'overview'"
                :class="activeTab === 'overview' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium'"
                class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition whitespace-nowrap flex items-center gap-2">
                <span>📝 Product Overview &amp; Applications</span>
            </button>

            <button type="button" @click="activeTab = 'documents'"
                :class="activeTab === 'documents' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium'"
                class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition whitespace-nowrap flex items-center gap-2">
                <span>📄 Technical Data &amp; Compliance Certificates</span>
                @if($product->documents->isNotEmpty())
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-50 text-indigo-700 font-bold border border-indigo-200">{{ $product->documents->count() }}</span>
                @endif
            </button>

            <button type="button" @click="activeTab = 'wholesale'"
                :class="activeTab === 'wholesale' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium'"
                class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition whitespace-nowrap flex items-center gap-2">
                <span>🚛 Bulk Logistics &amp; Pallet Rates</span>
            </button>

            <button type="button" @click="activeTab = 'reviews'"
                :class="activeTab === 'reviews' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-medium'"
                class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition whitespace-nowrap flex items-center gap-2">
                <span>★ Customer Reviews &amp; Ratings</span>
            </button>
        </div>

        {{-- Tab Content Panes --}}
        <div class="p-6 sm:p-8">
            {{-- TAB 1: Specifications --}}
            <div x-show="activeTab === 'specs'" x-cloak class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Technical Specifications &amp; Attributes</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Authoritative manufacturing standards and physical parameters</p>
                </div>

                {{-- Product-Level Technical Specs (from attribute definitions) --}}
                @if($product->attributeValues->isNotEmpty())
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Product Specifications
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-0 text-xs">
                            @foreach($product->attributeValues->sortBy('definition.sort_order') as $pav)
                                <div class="py-2.5 border-b border-slate-100 flex justify-between gap-3">
                                    <span class="text-slate-500 shrink-0">{{ $pav->definition->name }}</span>
                                    <span class="font-bold text-slate-900 text-right">
                                        @if($pav->value_number !== null && $pav->value_text)
                                            {{ $pav->value_number }} {{ $pav->value_text }}
                                        @else
                                            {{ $pav->display_value ?: '—' }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Core Logistics & Identity --}}
                <div>
                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Product Identity &amp; Logistics
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-0 text-xs">
                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Manufacturer / Brand</span>
                            <span class="font-bold text-slate-900">{{ $product->brand?->name ?? 'Industrial Grade' }}</span>
                        </div>

                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Primary Category</span>
                            <span class="font-bold text-slate-900">{{ $product->primaryCategory?->name ?? 'General Supplies' }}</span>
                        </div>

                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Standard SKU</span>
                            <span class="font-mono font-bold text-slate-900" x-text="selectedSku">{{ $selectedVariant?->sku }}</span>
                        </div>

                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Packaging Unit</span>
                            <span class="font-bold text-slate-900" x-text="selectedUnit">{{ $selectedVariant?->unit }}</span>
                        </div>

                        <template x-if="selectedPackSize">
                            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                                <span class="text-slate-500">Pack Size</span>
                                <span class="font-bold text-slate-900" x-text="selectedPackSize"></span>
                            </div>
                        </template>

                        <template x-if="selectedWeight">
                            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                                <span class="text-slate-500">Unit Weight</span>
                                <span class="font-bold text-slate-900" x-text="selectedWeight + ' KG'"></span>
                            </div>
                        </template>

                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Tax Classification</span>
                            <span class="font-bold text-slate-900" x-text="selectedTaxName + ' (' + selectedTaxRate + '%)'"></span>
                        </div>

                        <div class="py-2.5 border-b border-slate-100 flex justify-between">
                            <span class="text-slate-500">Fulfillment Hub</span>
                            <span class="font-bold text-indigo-600" x-text="selectedWarehouse"></span>
                        </div>
                    </div>
                </div>

                {{-- Variant-level attributes --}}
                @if($selectedVariant?->attributeValues->isNotEmpty())
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Variant Specifications
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-0 text-xs">
                            @foreach($selectedVariant->attributeValues as $av)
                                <div class="py-2.5 border-b border-slate-100 flex justify-between">
                                    <span class="text-slate-500">{{ $av->definition->name }}</span>
                                    <span class="font-bold text-slate-900">{{ $av->display_value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- TAB 2: Overview & Applications --}}
            <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Product Formulation &amp; Performance Highlights</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Engineering specifications and site application guidelines</p>
                </div>

                <div class="prose prose-sm max-w-none text-slate-700 text-xs sm:text-sm leading-relaxed space-y-4">
                    <p>{{ $product->description ?? $product->short_description ?? 'High-strength industrial construction material formulated for structural reliability, heavy RCC casting, and commercial applications.' }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <h4 class="font-bold text-slate-900 text-xs mb-1">Recommended Applications</h4>
                        <p class="text-[11px] text-slate-500">Heavy structural RCC, foundations, columns, beams, slab casting, and durable external plastering.</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <h4 class="font-bold text-slate-900 text-xs mb-1">Curing &amp; Setting Parameters</h4>
                        <p class="text-[11px] text-slate-500">Initial setting time &gt; 30 minutes, final setting &lt; 600 minutes. Continuous moist curing recommended for minimum 14 days.</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <h4 class="font-bold text-slate-900 text-xs mb-1">Storage &amp; Handling</h4>
                        <p class="text-[11px] text-slate-500">Store off ground on wooden pallets in dry, weatherproof covered sheds away from moisture and humidity.</p>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Documents & Compliance --}}
            <div x-show="activeTab === 'documents'" x-cloak class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Technical Data &amp; Compliance Certificates</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Official manufacturer test certificates, MSDS/SDS sheets, and regulatory approvals</p>
                </div>

                @if($product->documents->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($product->documents as $doc)
                            <div class="p-4 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-4 shadow-2xs hover:border-indigo-300 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-black text-xs uppercase shrink-0 border border-rose-100">
                                        PDF
                                    </div>
                                    <div class="truncate">
                                        <div class="text-xs font-bold text-slate-900 truncate">{{ $doc->title }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">Official Technical Specification File</div>
                                    </div>
                                </div>
                                <a href="{{ $doc->url }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold rounded-lg transition shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>Download</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 text-xs text-slate-400">
                        No technical datasheets or compliance certificates attached to this product yet.
                    </div>
                @endif
            </div>

            {{-- TAB 4: Wholesale & Logistics --}}
            <div x-show="activeTab === 'wholesale'" x-cloak class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Wholesale Volume Pricing &amp; Fleet Logistics</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Commercial contractor supply, full truckload logistics, and GST input credit claim guide</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                        <h4 class="font-bold text-slate-900">Direct Contractor Pallet Delivery</h4>
                        <p class="text-slate-600 leading-relaxed">
                            Materials are dispatched from our central hub on shrink-wrapped, moisture-barrier wooden pallets. Standard pallet packaging is 40 bags (2.0 Metric Tons). Site crane or forklift unloading can be requested during checkout.
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                        <h4 class="font-bold text-slate-900">GST Input Tax Credit (ITC) Documentation</h4>
                        <p class="text-slate-600 leading-relaxed">
                            Every delivery includes a tax invoice reflecting your corporate GSTIN and legal business name. As this material falls under the statutory 28% GST bracket, full ITC credit can be claimed against output tax liabilities.
                        </p>
                    </div>
                </div>
            </div>

            {{-- TAB 5: Customer Reviews --}}
            <div x-show="activeTab === 'reviews'" x-cloak>
                @if(app(\App\Core\Services\SettingService::class)->get('features.reviews_enabled', true))
                    @include('storefront.components.reviews', ['product' => $product])
                @endif
            </div>
        </div>
    </div>

    <!-- Related Products in Category (Balanced Showcase) -->
    @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <div class="mt-12 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Frequently Bought Together &amp; Related Materials</h3>
                    <p class="text-xs text-slate-500">Matching products from {{ $product->primaryCategory?->name ?? 'our catalog' }}</p>
                </div>
                @if($product->primaryCategory)
                    <a href="{{ route('storefront.catalog', ['category' => $product->primaryCategory->slug]) }}"
                        class="text-xs font-bold text-indigo-600 hover:underline">
                        View All in {{ $product->primaryCategory->name }} →
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach($relatedProducts as $rel)
                    @php
                        $relVariant = $rel->variants->first();
                        $relPrice = $relVariant ? $relVariant->selling_price / 100 : 0;
                        $relMrp = $relVariant ? $relVariant->mrp / 100 : 0;
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden p-3.5 flex flex-col justify-between hover:shadow-sm hover:border-slate-300 transition duration-150 group">
                        <div>
                            <div class="aspect-square rounded-xl overflow-hidden bg-slate-50 flex items-center justify-center mb-2.5 p-2">
                                @if($rel->primaryMedia)
                                    <img src="{{ $rel->primaryMedia->url }}" alt="{{ $rel->name }}" class="w-full h-full object-contain group-hover:scale-105 transition duration-200">
                                @else
                                    <span class="text-2xl text-slate-300">📦</span>
                                @endif
                            </div>

                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block">
                                {{ $rel->brand?->name ?? 'Quality Supply' }}
                            </span>
                            <h4 class="font-bold text-slate-900 text-xs mt-0.5 line-clamp-2 leading-snug">
                                {{ $rel->name }}
                            </h4>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs sm:text-sm font-black text-slate-900 font-mono">₹{{ number_format($relPrice, 2) }}</span>
                                @if($relMrp > $relPrice)
                                    <span class="text-[10px] text-slate-400 line-through block font-mono">₹{{ number_format($relMrp, 2) }}</span>
                                @endif
                            </div>
                            <a href="{{ route('storefront.product', $rel->slug) }}"
                                class="px-2.5 py-1 bg-slate-900 hover:bg-indigo-600 text-white font-bold text-[11px] rounded-lg transition shadow-2xs">
                                View
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('schema')
@php
    $schemaData = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => strip_tags($product->description ?? $product->name),
        'sku' => $selectedVariant->sku,
        'offers' => [
            '@type' => 'Offer',
            'url' => url()->current(),
            'priceCurrency' => 'INR',
            'price' => number_format($selectedVariant->selling_price / 100, 2, '.', ''),
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];

    if ($product->primaryMedia) {
        $schemaData['image'] = $product->primaryMedia->url;
    }

    if ($product->brand) {
        $schemaData['brand'] = [
            '@type' => 'Brand',
            'name' => $product->brand->name,
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
