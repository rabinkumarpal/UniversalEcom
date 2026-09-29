@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Add Catalog Offer')
@section('subtitle', 'Offer a product variant from the platform catalog with your price, stock and live margin preview')

@section('content')
<div class="max-w-3xl mx-auto"
     x-data="{
         selectedVariantId: '{{ old('product_variant_id', '') }}',
         vendorPrice: '{{ old('vendor_price', '') }}',
         vendorMrp: '{{ old('vendor_mrp', '') }}',
         initialStock: '{{ old('initial_stock', 50) }}',
         commissionPercentage: {{ (float) ($vendor->commission_rate_percentage ?? 10) }},
         variants: {
             @foreach($variants as $variant)
                 '{{ $variant->id }}': {
                     id: {{ $variant->id }},
                     name: '{{ addslashes($variant->product->name ?? 'Product') }} — {{ addslashes($variant->name) }}',
                     sku: '{{ addslashes($variant->sku) }}',
                     catalogPrice: {{ $variant->selling_price / 100 }},
                     catalogMrp: {{ $variant->mrp ? $variant->mrp / 100 : 0 }},
                 },
             @endforeach
         },
         get currentVariant() {
             return this.variants[this.selectedVariantId] || null;
         },
         get effectiveMrp() {
             if (this.vendorMrp && parseFloat(this.vendorMrp) > 0) {
                 return parseFloat(this.vendorMrp);
             }
             return this.currentVariant ? this.currentVariant.catalogMrp : 0;
         },
         get price() {
             return parseFloat(this.vendorPrice) || 0;
         },
         get stock() {
             return parseInt(this.initialStock) || 0;
         },
         get commissionAmount() {
             return (this.price * (this.commissionPercentage / 100));
         },
         get netPayout() {
             return Math.max(0, this.price - this.commissionAmount);
         },
         get marginPercent() {
             return this.price > 0 ? ((this.netPayout / this.price) * 100) : 0;
         },
         get projectedTotalSales() {
             return this.price * this.stock;
         },
         get projectedNetPayout() {
             return this.netPayout * this.stock;
         },
         get isExceedingMrp() {
             return this.effectiveMrp > 0 && this.price > this.effectiveMrp;
         },
         get isExceedingCatalogMrp() {
             let catMrp = this.currentVariant ? this.currentVariant.catalogMrp : 0;
             let vMrp = parseFloat(this.vendorMrp) || 0;
             return catMrp > 0 && vMrp > catMrp;
         },
         get priceDifferenceCatalog() {
             if (!this.currentVariant || !this.currentVariant.catalogPrice) {
                 return 0;
             }
             return this.price - this.currentVariant.catalogPrice;
         },
         onVariantChange() {
             if (this.currentVariant && (!this.vendorMrp || this.vendorMrp === '0')) {
                 if (this.currentVariant.catalogMrp > 0) {
                     this.vendorMrp = this.currentVariant.catalogMrp.toFixed(2);
                 }
             }
             if (this.currentVariant && (!this.vendorPrice || this.vendorPrice === '0')) {
                 if (this.currentVariant.catalogPrice > 0) {
                     this.vendorPrice = this.currentVariant.catalogPrice.toFixed(2);
                 }
             }
         }
     }">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-rose-900">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.offers.store') }}" class="space-y-6">
            @csrf

            <!-- Product Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Catalog Variant</label>
                <select name="product_variant_id" required x-model="selectedVariantId" @change="onVariantChange()"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                    <option value="">-- Choose a product variant --</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}">
                            {{ $variant->product->name ?? 'Product' }} — {{ $variant->name }} (SKU: {{ $variant->sku }}) [Base Selling: ₹{{ number_format($variant->selling_price / 100, 2) }} @if($variant->mrp)| MRP: ₹{{ number_format($variant->mrp / 100, 2) }}@endif]
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Select from verified manufacturer products available on the Universal marketplace.</p>
            </div>

            <!-- Price and MRP Inputs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Selling Price -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Your Selling Price (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 font-bold">₹</span>
                        <input type="number" step="0.01" min="0.01" name="vendor_price" x-model="vendorPrice" required
                               placeholder="e.g. 420.00"
                               :class="isExceedingMrp ? 'border-rose-400 ring-2 ring-rose-100 bg-rose-50/30' : 'border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'"
                               class="w-full pl-8 pr-3 py-2.5 rounded-xl border text-sm font-mono transition-colors">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Final price customer pays on checkout</p>
                </div>

                <!-- Maximum Retail Price (MRP) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Maximum Retail Price / MRP (₹)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 font-bold">₹</span>
                        <input type="number" step="0.01" min="0.01" name="vendor_mrp" x-model="vendorMrp"
                               placeholder="e.g. 460.00"
                               :class="isExceedingCatalogMrp ? 'border-amber-400 ring-2 ring-amber-100' : 'border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'"
                               class="w-full pl-8 pr-3 py-2.5 rounded-xl border text-sm font-mono transition-colors">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Printed packaging MRP ceiling
                        <template x-if="currentVariant && currentVariant.catalogMrp > 0">
                            <span>(Catalog: ₹<strong class="font-mono text-slate-600" x-text="currentVariant.catalogMrp.toFixed(2)"></strong>)</span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- Price Guardrail Warnings -->
            <div x-show="isExceedingMrp" x-cloak class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h5 class="font-bold text-rose-900">Offer Price Guardrail Violation</h5>
                    <p class="mt-0.5 leading-relaxed">
                        Your selling price of <span class="font-mono font-bold text-rose-900">₹<span x-text="price.toFixed(2)"></span></span> exceeds the Maximum Retail Price of <span class="font-mono font-bold text-rose-900">₹<span x-text="effectiveMrp.toFixed(2)"></span></span>. Under e-commerce legal metrology regulations and consumer protection rules, retail selling prices can never exceed the product MRP.
                    </p>
                </div>
            </div>

            <div x-show="isExceedingCatalogMrp" x-cloak class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <span class="font-bold">Catalog MRP Notice:</span>
                    The MRP you entered (₹<span x-text="parseFloat(vendorMrp).toFixed(2)"></span>) exceeds the manufacturer packaging MRP in the platform catalog (₹<span x-text="currentVariant ? currentVariant.catalogMrp.toFixed(2) : '0.00'"></span>).
                </div>
            </div>

            <!-- SKU and Initial Inventory -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Your Internal Vendor SKU</label>
                    <input type="text" name="vendor_sku" value="{{ old('vendor_sku') }}"
                           placeholder="e.g. VEND-CEM-50KG"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Optional internal stock keeping code</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Available Inventory</label>
                    <input type="number" min="0" name="initial_stock" x-model="initialStock"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Units immediately available in your warehouse</p>
                </div>
            </div>

            <!-- Real-Time Margin & Payout Calculator Widget -->
            <div class="bg-slate-900 text-white rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-amber-500/20 text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-200">Real-Time Margin & Net Payout Preview</h4>
                    </div>
                    <span class="text-[11px] font-mono text-slate-400">
                        Platform Fee: <strong class="text-amber-400">{{ $vendor->commission_rate_percentage }}%</strong>
                    </span>
                </div>

                <div x-show="price > 0" class="space-y-3.5">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="p-3 bg-slate-800/70 rounded-xl border border-slate-700/60">
                            <div class="text-[10px] uppercase font-bold text-slate-400">Customer Price</div>
                            <div class="text-sm font-mono font-bold text-white mt-0.5">₹<span x-text="price.toFixed(2)"></span></div>
                            <div class="text-[9px] text-slate-400">Gross Sale</div>
                        </div>
                        <div class="p-3 bg-slate-800/70 rounded-xl border border-slate-700/60">
                            <div class="text-[10px] uppercase font-bold text-rose-400">Commission (<span x-text="commissionPercentage"></span>%)</div>
                            <div class="text-sm font-mono font-bold text-rose-400 mt-0.5">-₹<span x-text="commissionAmount.toFixed(2)"></span></div>
                            <div class="text-[9px] text-slate-400">Platform Retained</div>
                        </div>
                        <div class="p-3 bg-emerald-950/60 rounded-xl border border-emerald-800/60">
                            <div class="text-[10px] uppercase font-bold text-emerald-400">Net Unit Payout</div>
                            <div class="text-base font-mono font-black text-emerald-300 mt-0.5">₹<span x-text="netPayout.toFixed(2)"></span></div>
                            <div class="text-[9px] text-emerald-400/80">Credited to Balance</div>
                        </div>
                        <div class="p-3 bg-slate-800/70 rounded-xl border border-slate-700/60">
                            <div class="text-[10px] uppercase font-bold text-slate-400">Payout Margin</div>
                            <div class="text-sm font-mono font-bold text-amber-300 mt-0.5"><span x-text="marginPercent.toFixed(1)"></span>%</div>
                            <div class="text-[9px] text-slate-400">Of Customer Price</div>
                        </div>
                    </div>

                    <!-- Projected Batch Earnings -->
                    <template x-if="stock > 0">
                        <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/60 text-xs flex flex-wrap items-center justify-between gap-2 text-slate-300">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>Projected Payout for Initial Batch (<strong class="text-white" x-text="stock"></strong> units):</span>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-black text-emerald-400 text-sm">₹<span x-text="projectedNetPayout.toFixed(2)"></span></span>
                                <span class="text-[10px] text-slate-400 block font-mono">(Gross: ₹<span x-text="projectedTotalSales.toFixed(2)"></span>)</span>
                            </div>
                        </div>
                    </template>

                    <!-- Competitive Benchmark Against Catalog Base -->
                    <template x-if="currentVariant && currentVariant.catalogPrice > 0">
                        <div class="text-[11px] p-2.5 rounded-xl bg-slate-800/40 border border-slate-800 flex items-center gap-2">
                            <span x-show="priceDifferenceCatalog < 0" class="text-emerald-400 font-medium inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                <span><strong>High Buy-Box Advantage:</strong> Priced ₹<span x-text="Math.abs(priceDifferenceCatalog).toFixed(2)"></span> below platform catalog base (₹<span x-text="currentVariant.catalogPrice.toFixed(2)"></span>).</span>
                            </span>
                            <span x-show="priceDifferenceCatalog === 0" class="text-slate-300 inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Matches platform catalog base price (₹<span x-text="currentVariant.catalogPrice.toFixed(2)"></span>).</span>
                            </span>
                            <span x-show="priceDifferenceCatalog > 0" class="text-amber-300 inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Priced ₹<span x-text="priceDifferenceCatalog.toFixed(2)"></span> above catalog base price (₹<span x-text="currentVariant.catalogPrice.toFixed(2)"></span>).</span>
                            </span>
                        </div>
                    </template>
                </div>

                <div x-show="!price || price <= 0" class="text-center py-4 text-xs text-slate-400">
                    Select a catalog variant and type your selling price above to preview real-time commission deductions and net payout calculations.
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="pt-2 flex items-center justify-end gap-3">
                <a href="{{ route('vendor.offers.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="isExceedingMrp || !price || !selectedVariantId"
                        :class="(isExceedingMrp || !price || !selectedVariantId) ? 'opacity-50 cursor-not-allowed bg-slate-300 text-slate-600' : 'bg-amber-500 hover:bg-amber-600 text-slate-950 cursor-pointer shadow-md'"
                        class="px-6 py-2.5 font-black text-xs rounded-xl transition-all flex items-center gap-2">
                    <span x-show="!isExceedingMrp">Publish Offer</span>
                    <span x-show="isExceedingMrp">Price Exceeds MRP</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
