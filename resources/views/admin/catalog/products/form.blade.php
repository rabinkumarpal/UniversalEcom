@extends('layouts.admin')

@section('title', $product ? "Edit Product: {$product->name}" : 'Add New Product')
@section('header_title', $product ? 'Edit Product' : 'Add New Product')
@section('header_subtitle', $product ? "Managing catalog record #{$product->id} — {$product->name}" : 'Create a new product with variants, wholesale tiers, images, and taxonomy')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'general',
    name: '{{ addslashes(old('name', $product?->name ?? '')) }}',
    slug: '{{ addslashes(old('slug', $product?->slug ?? '')) }}',
    seoTitle: '{{ addslashes(old('seo_title', $product?->seo_title ?? '')) }}',
    seoDescription: '{{ addslashes(old('seo_description', $product?->seo_description ?? '')) }}',
    status: '{{ old('status', $product?->status ?? 'draft') }}',
    isDirty: false,
    markDirty() { this.isDirty = true; },
    scrollTo(id) {
        this.activeTab = id;
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}" @input="markDirty()">

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs shadow-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Please fix the following validation issues:
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
        id="product-form"
        action="{{ $product ? route('admin.catalog.products.update', $product->id) : route('admin.catalog.products.store') }}"
        enctype="multipart/form-data">
        @csrf
        @if($product) @method('PUT') @endif

        {{-- Hidden containers for dynamic deletions --}}
        <div id="deleted-variants-container"></div>
        <div id="deleted-media-container"></div>
        <div id="deleted-documents-container"></div>

        {{-- Sticky Header Bar --}}
        <div class="sticky top-0 z-30 -mt-2 mb-6 py-3 px-4 sm:px-6 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm flex flex-wrap items-center justify-between gap-3 transition-all">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.catalog.products.index') }}" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="truncate">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-black text-slate-900 dark:text-white truncate">
                            {{ $product ? $product->name : 'New Catalog Product' }}
                        </h2>
                        @if($product)
                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full uppercase tracking-wider
                                {{ $product->status === 'published' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($product->status === 'draft' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700') }}">
                                {{ $product->status }}
                            </span>
                        @endif
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                        <span>ID: #{{ $product?->id ?? 'New' }}</span>
                        @if($product?->primaryCategory)
                            <span>•</span>
                            <span>{{ $product->primaryCategory->name }}</span>
                        @endif
                        <span x-show="isDirty" x-cloak class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Unsaved changes
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                @if($product)
                    <a href="{{ route('storefront.product', $product->slug) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Storefront ↗</span>
                    </a>
                @endif

                <a href="{{ route('admin.catalog.products.index') }}" class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                    Discard
                </a>

                @if($product)
                    <button type="submit" name="action" value="save_and_continue"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition shadow-2xs">
                        Save &amp; Stay
                    </button>
                @endif

                <button type="submit" name="action" value="save"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm hover:shadow-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ $product ? 'Save Changes' : 'Create Product' }}</span>
                </button>
            </div>
        </div>

        {{-- Anchor Jump Pills --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs font-semibold text-slate-600 dark:text-slate-400 no-scrollbar">
            <button type="button" @click="scrollTo('section-general')"
                :class="activeTab === 'section-general' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                <span>📝 General</span>
            </button>
            <button type="button" @click="scrollTo('section-media')"
                :class="activeTab === 'section-media' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                <span>🖼️ Media Gallery</span>
                @if($product && $product->media->isNotEmpty())
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $product->media->count() }}</span>
                @endif
            </button>
            <button type="button" @click="scrollTo('section-variants')"
                :class="activeTab === 'section-variants' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                <span>📦 Variants &amp; Pricing</span>
                @if($product && $product->variants->isNotEmpty())
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold">{{ $product->variants->count() }}</span>
                @endif
            </button>
            <button type="button" @click="scrollTo('section-tiers')"
                :class="activeTab === 'section-tiers' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                <span>💰 Wholesale Tiers</span>
            </button>
            <button type="button" @click="scrollTo('section-attributes')"
                :class="activeTab === 'section-attributes' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                <span>🏷️ Tech Specs</span>
                @if($product && $product->attributeValues->isNotEmpty())
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-bold">{{ $product->attributeValues->count() }}</span>
                @endif
            </button>
            @if($product)
                <button type="button" @click="scrollTo('section-inventory')"
                    :class="activeTab === 'section-inventory' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                    class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                    <span>🏭 Warehouse Stock</span>
                </button>
                <button type="button" @click="scrollTo('section-documents')"
                    :class="activeTab === 'section-documents' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                    class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                    <span>📄 Documents</span>
                    @if($product->documents->isNotEmpty())
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $product->documents->count() }}</span>
                    @endif
                </button>
                <button type="button" @click="scrollTo('section-audit')"
                    :class="activeTab === 'section-audit' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900'"
                    class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                    <span>📜 History &amp; Audits</span>
                </button>
            @endif
        </div>

        {{-- 2-Column Responsive Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Main Column (8 cols) --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- SECTION 1: General Information --}}
                <div id="section-general" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Basic Information</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Core identifiers, title, and descriptive catalog copy.</p>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Required *</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Product Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="name" value="{{ old('name', $product?->name) }}" required
                                placeholder="e.g. UltraTech Super PPC High Strength Cement"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 font-medium focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                        </div>

                        @if($product)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Storefront URL Slug</label>
                                <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900/80 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-mono text-slate-600 dark:text-slate-300">
                                    <span class="text-slate-400">/product/</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $product->slug }}</span>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Short Description / Catalog Highlight
                            </label>
                            <textarea name="short_description" rows="2"
                                placeholder="A concise 1-2 sentence overview shown in product cards, quotes, and delivery notes…"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('short_description', $product?->short_description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Detailed Product Description &amp; Technical Specifications
                            </label>
                            <textarea name="description" rows="6"
                                placeholder="Comprehensive specifications, formulation, application guidelines, curing instructions, and regulatory compliance standards…"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('description', $product?->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Media & Gallery --}}
                <div id="section-media" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Media &amp; Gallery</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">High-resolution photography, packaging shots, and primary store badge.</p>
                        </div>
                        <button type="button" onclick="openMediaModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-xl border border-indigo-200 dark:border-indigo-800 transition">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Choose from Media Library
                        </button>
                    </div>

                    {{-- Existing Product Images Gallery --}}
                    @if($product && $product->media->isNotEmpty())
                        <div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center justify-between">
                                <span>Current Product Photos ({{ $product->media->count() }}):</span>
                                <span class="text-[11px] text-slate-400">Order numbers define display sequence</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach($product->media as $media)
                                    <div class="relative bg-slate-50 dark:bg-slate-900 border {{ $media->is_primary ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800' }} rounded-2xl overflow-hidden p-2.5 flex flex-col justify-between group">
                                        <div class="aspect-square rounded-xl overflow-hidden bg-white dark:bg-slate-950 flex items-center justify-center relative">
                                            <img src="{{ $media->url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                            @if($media->is_primary)
                                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[9px] font-black bg-indigo-600 text-white shadow-xs">
                                                    PRIMARY
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-2.5 space-y-2 text-[11px]">
                                            <div class="flex items-center justify-between">
                                                <label class="flex items-center gap-1.5 cursor-pointer font-semibold {{ $media->is_primary ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900' }}">
                                                    <input type="radio" name="primary_media_id" value="{{ $media->id }}" @checked($media->is_primary)
                                                        class="text-indigo-600 focus:ring-indigo-500">
                                                    <span>{{ $media->is_primary ? 'Primary' : 'Set Primary' }}</span>
                                                </label>

                                                <label class="flex items-center gap-1 cursor-pointer text-rose-600 hover:text-rose-700">
                                                    <input type="checkbox" name="delete_media_ids[]" value="{{ $media->id }}"
                                                        class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                                    <span>Remove</span>
                                                </label>
                                            </div>

                                            <div class="flex items-center gap-2 pt-1 border-t border-slate-200 dark:border-slate-800">
                                                <span class="text-[10px] text-slate-400">Order:</span>
                                                <input type="number" name="media_sort_orders[{{ $media->id }}]" value="{{ $media->sort_order }}" min="0"
                                                    class="w-16 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md px-2 py-0.5 text-[11px] text-center font-mono">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Direct Upload Dropzone --}}
                    <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center bg-slate-50/50 dark:bg-slate-900/30 hover:border-indigo-400 transition cursor-pointer">
                        <input type="file" name="images[]" id="product-images-input" multiple accept="image/*" class="hidden" onchange="handleImageSelect(event)">
                        <label for="product-images-input" class="cursor-pointer flex flex-col items-center justify-center gap-2">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-2xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Click to upload new images</span>
                                <span class="text-xs text-slate-500"> or drag files here</span>
                            </div>
                            <p class="text-[11px] text-slate-400">PNG, JPG, WEBP up to 10MB each. Automatic WebP optimization on upload.</p>
                        </label>
                    </div>

                    {{-- Local Upload Previews --}}
                    <div id="selected-previews" class="grid grid-cols-3 sm:grid-cols-6 gap-3 hidden"></div>

                    {{-- Selected Library Assets Container --}}
                    <div id="library-assets-container" class="space-y-2 hidden">
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Selected from Media Asset Library:</div>
                        <div id="library-assets-grid" class="grid grid-cols-3 sm:grid-cols-6 gap-3"></div>
                    </div>
                </div>

                {{-- SECTION 3: Variants, Packaging & Pricing Matrix --}}
                <div id="section-variants" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Product Variants &amp; Pricing Matrix</span>
                                <span class="text-rose-500">*</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Sellable SKUs, packaging units, MRP, selling prices, tax, and shipping weights.</p>
                        </div>
                        <button type="button" onclick="addVariant()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-xl border border-indigo-200 dark:border-indigo-800 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Variant
                        </button>
                    </div>

                    <div id="variants-container" class="space-y-4">
                        @php
                            $existingVariants = ($product && $product->variants->isNotEmpty()) ? $product->variants : collect([null]);
                        @endphp
                        @foreach($existingVariants as $idx => $v)
                            @php
                                $mrpVal = $v ? $v->mrp / 100 : (float) old('variants.'.$idx.'.mrp', 0);
                                $sellingVal = $v ? $v->selling_price / 100 : (float) old('variants.'.$idx.'.selling_price', 0);
                                $discountPct = ($mrpVal > 0 && $mrpVal >= $sellingVal) ? round((($mrpVal - $sellingVal) / $mrpVal) * 100, 1) : 0;
                            @endphp
                            <div class="variant-row bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3.5 relative transition-all"
                                 x-data="{
                                    mrp: {{ $mrpVal }},
                                    selling: {{ $sellingVal }},
                                    get discount() {
                                        if (this.mrp > 0 && this.mrp >= this.selling) {
                                            return Math.round(((this.mrp - this.selling) / this.mrp) * 100);
                                        }
                                        return 0;
                                    },
                                    get isLoss() {
                                        return this.mrp > 0 && this.selling > this.mrp;
                                    }
                                 }">
                                @if($v)
                                    <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $v->id }}">
                                @endif

                                <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-black text-xs flex items-center justify-center">
                                            #{{ $idx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ $v?->name ?? 'New Variant Row' }}
                                        </span>
                                        @if($v?->sku)
                                            <span class="px-2 py-0.5 text-[10px] font-mono bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                                                {{ $v->sku }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span x-show="discount > 0 && !isLoss" x-cloak
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span x-text="discount"></span>% OFF MRP
                                        </span>
                                        <span x-show="isLoss" x-cloak
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            ⚠ Selling Price exceeds MRP
                                        </span>

                                        @if($v)
                                            <button type="button" onclick="removeExistingVariant(this, {{ $v->id }})" title="Remove or archive this variant"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @else
                                            <button type="button" onclick="removeVariantRow(this)" title="Remove row"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Row 1: Identification & Pricing --}}
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-4">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Variant Title *</label>
                                        <input type="text" name="variants[{{ $idx }}][name]" required value="{{ old('variants.'.$idx.'.name', $v?->name) }}"
                                            placeholder="e.g. 50 KG Sealed Bag"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">SKU *</label>
                                        <input type="text" name="variants[{{ $idx }}][sku]" required value="{{ old('variants.'.$idx.'.sku', $v?->sku) }}"
                                            placeholder="UT-PPC-50KG"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">MRP (₹) *</label>
                                        <input type="number" step="0.01" min="0" name="variants[{{ $idx }}][mrp]" required
                                            x-model.number="mrp"
                                            value="{{ old('variants.'.$idx.'.mrp', $v ? number_format($v->mrp / 100, 2, '.', '') : '') }}"
                                            placeholder="480.00"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Selling Price (₹) *</label>
                                        <input type="number" step="0.01" min="0" name="variants[{{ $idx }}][selling_price]" required
                                            x-model.number="selling"
                                            value="{{ old('variants.'.$idx.'.selling_price', $v ? number_format($v->selling_price / 100, 2, '.', '') : '') }}"
                                            placeholder="450.00"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                </div>

                                {{-- Row 2: Logistics, Tax & Packaging Specs --}}
                                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 pt-1">
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Unit *</label>
                                        <input type="text" name="variants[{{ $idx }}][unit]" required value="{{ old('variants.'.$idx.'.unit', $v?->unit ?? 'bag') }}"
                                            placeholder="bag, kg…"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Pack Size Label</label>
                                        <input type="text" name="variants[{{ $idx }}][pack_size]" value="{{ old('variants.'.$idx.'.pack_size', $v?->pack_size ?? '50 KG') }}"
                                            placeholder="e.g. 50 KG Bag"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1" title="Weight in Kilograms used for freight and delivery rates">
                                            Weight (kg)
                                        </label>
                                        <input type="number" step="0.001" min="0" name="variants[{{ $idx }}][weight_kg]" value="{{ old('variants.'.$idx.'.weight_kg', $v?->weight_kg) }}"
                                            placeholder="50.0"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500 font-mono">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Tax Bracket</label>
                                        <select name="variants[{{ $idx }}][tax_class_id]"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                                            <option value="">No Tax Class (Default)</option>
                                            @foreach($taxClasses as $tc)
                                                <option value="{{ $tc->id }}" @selected(old('variants.'.$idx.'.tax_class_id', $v?->tax_class_id) == $tc->id)>
                                                    {{ $tc->name }} ({{ $tc->rate_percentage }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Status</label>
                                        <select name="variants[{{ $idx }}][status]"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                                            <option value="active" @selected(old('variants.'.$idx.'.status', $v?->status ?? 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected(old('variants.'.$idx.'.status', $v?->status) === 'inactive')>Inactive</option>
                                            <option value="archived" @selected(old('variants.'.$idx.'.status', $v?->status) === 'archived')>Archived</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- SECTION 4: Wholesale B2B Quantity Price Tiers --}}
                <div id="section-tiers" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>B2B Wholesale Quantity Price Tiers</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Automated volume discounts unlocked at checkout when contractors or bulk buyers reach minimum quantities.</p>
                        </div>
                    </div>

                    @php
                        $variantForTiers = $product?->variants->first();
                        $tiers = $variantForTiers?->quantityTiers ?? collect();
                    @endphp

                    <div class="space-y-3">
                        <div class="bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/60 rounded-xl p-3 flex items-start gap-2.5 text-xs text-indigo-900 dark:text-indigo-200">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <span class="font-bold">Automated Volume Discounts:</span> Standard selling price is charged for quantities below Tier 1. When a cart line quantity meets a tier range, the discounted unit price is automatically applied.
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-2 px-3">Variant</th>
                                        <th class="py-2 px-3">Min Quantity</th>
                                        <th class="py-2 px-3">Max Quantity</th>
                                        <th class="py-2 px-3">Unit Price (₹)</th>
                                        <th class="py-2 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tiers-table-body" class="divide-y divide-slate-100 dark:divide-slate-900">
                                    @foreach($existingVariants as $vIdx => $variant)
                                        @php
                                            $vTiers = $variant?->quantityTiers ?? collect();
                                        @endphp
                                        @foreach($vTiers as $tIdx => $tier)
                                            <tr class="tier-row hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                                <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300">
                                                    {{ $variant->name }} ({{ $variant->sku }})
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <input type="number" min="1" name="variants[{{ $vIdx }}][tiers][{{ $tIdx }}][min_quantity]" value="{{ $tier->min_quantity }}" required
                                                        class="w-24 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs font-mono">
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <input type="number" min="1" name="variants[{{ $vIdx }}][tiers][{{ $tIdx }}][max_quantity]" value="{{ $tier->max_quantity }}" placeholder="No limit (+)"
                                                        class="w-24 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs font-mono">
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <div class="relative w-32">
                                                        <span class="absolute left-2.5 top-1.5 text-slate-400 text-xs">₹</span>
                                                        <input type="number" step="0.01" min="0" name="variants[{{ $vIdx }}][tiers][{{ $tIdx }}][unit_price]" value="{{ number_format($tier->unit_price / 100, 2, '.', '') }}" required
                                                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-6 pr-2 py-1 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                                    </div>
                                                </td>
                                                <td class="py-2.5 px-3 text-right">
                                                    <button type="button" onclick="this.closest('.tier-row').remove()" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" onclick="addTierRow()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Volume Price Tier
                        </button>
                    </div>
                </div>

                {{-- SECTION 5A: Technical Specs & Attributes --}}
                <div id="section-attributes" x-data="attributeSection()" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                🏷️ <span>Technical Specifications & Attributes</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Structured product spec data shown in the storefront spec sheet and used for catalog filtering.</p>
                        </div>
                        <a href="{{ route('admin.catalog.attributes.index') }}" target="_blank"
                            class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-medium flex items-center gap-1">
                            Manage Definitions
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>

                    @if($attributeDefinitions->isEmpty())
                        <div class="text-center py-8 text-slate-400 dark:text-slate-500 text-xs">
                            No attribute definitions yet.
                            <a href="{{ route('admin.catalog.attributes.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Create one →</a>
                        </div>
                    @else
                        {{-- Add attribute row --}}
                        <div class="flex items-center gap-2">
                            <select x-model="selectedDefId"
                                class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">— Select an attribute to add —</option>
                                @foreach($attributeDefinitions as $def)
                                    <option value="{{ $def->id }}"
                                        data-type="{{ $def->type }}"
                                        data-name="{{ $def->name }}"
                                        data-values="{{ json_encode($def->values->map(fn($v) => ['id' => $v->id, 'value' => $v->value, 'label' => $v->label ?? $v->value])) }}">
                                        {{ $def->name }}
                                        <span class="text-slate-400">({{ str_replace('_', ' ', $def->type) }})</span>
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="addAttribute()"
                                :disabled="!selectedDefId"
                                class="px-3 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Spec
                            </button>
                        </div>

                        {{-- Existing attribute rows --}}
                        @if($product && $product->attributeValues->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($product->attributeValues as $idx => $av)
                                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800">
                                        <input type="hidden" name="product_attributes[{{ $idx }}][attribute_definition_id]" value="{{ $av->attribute_definition_id }}">
                                        <div class="w-36 shrink-0">
                                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $av->definition->name }}</span>
                                            <span class="block text-[10px] text-slate-400 font-mono">{{ $av->definition->code }}</span>
                                        </div>
                                        <div class="flex-1">
                                            @if($av->definition->type === 'select')
                                                <select name="product_attributes[{{ $idx }}][attribute_value_id]"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                    <option value="">— Choose —</option>
                                                    @foreach($av->definition->values as $opt)
                                                        <option value="{{ $opt->id }}" @selected($av->attribute_value_id == $opt->id)>{{ $opt->label ?? $opt->value }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($av->definition->type === 'multi_select')
                                                <select name="product_attributes[{{ $idx }}][attribute_value_id]"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                    <option value="">— Choose —</option>
                                                    @foreach($av->definition->values as $opt)
                                                        <option value="{{ $opt->id }}" @selected($av->attribute_value_id == $opt->id)>{{ $opt->label ?? $opt->value }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($av->definition->type === 'boolean')
                                                <select name="product_attributes[{{ $idx }}][value_boolean]"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                    <option value="">— Choose —</option>
                                                    <option value="1" @selected($av->value_boolean === true)>Yes</option>
                                                    <option value="0" @selected($av->value_boolean === false)>No</option>
                                                </select>
                                            @elseif(in_array($av->definition->type, ['number', 'measurement']))
                                                <div class="flex items-center gap-1.5">
                                                    <input type="number" step="any" name="product_attributes[{{ $idx }}][value_number]"
                                                        value="{{ old("product_attributes.{$idx}.value_number", $av->value_number) }}"
                                                        placeholder="Enter value"
                                                        class="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                    @if($av->definition->type === 'measurement')
                                                        <input type="text" name="product_attributes[{{ $idx }}][value_text]"
                                                            value="{{ old("product_attributes.{$idx}.value_text", $av->value_text) }}"
                                                            placeholder="Unit (e.g. MPa, mm)"
                                                            class="w-24 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                    @endif
                                                </div>
                                            @else
                                                <input type="text" name="product_attributes[{{ $idx }}][value_text]"
                                                    value="{{ old("product_attributes.{$idx}.value_text", $av->value_text) }}"
                                                    placeholder="Enter text value"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                            @endif
                                        </div>
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">{{ str_replace('_', ' ', $av->definition->type) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400 dark:text-slate-500 text-center py-3" x-show="rows.length === 0">
                                No attributes assigned yet. Select an attribute above and click "Add Spec".
                            </p>
                        @endif

                        {{-- New attribute rows (Alpine) --}}
                        <div class="space-y-2" x-show="rows.length > 0">
                            <template x-for="(row, i) in rows" :key="row.tempId">
                                <div class="flex items-center gap-3 p-3 bg-violet-50/60 dark:bg-violet-950/30 rounded-xl border border-violet-200 dark:border-violet-800/50">
                                    <input type="hidden" :name="`product_attributes[existing_${i}][attribute_definition_id]`" :value="row.defId">
                                    <div class="w-36 shrink-0">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300" x-text="row.name"></span>
                                        <span class="block text-[10px] text-slate-400 font-mono" x-text="row.type.replace(/_/g, ' ')"></span>
                                    </div>
                                    <div class="flex-1">
                                        {{-- Select / Multi-select --}}
                                        <template x-if="row.type === 'select' || row.type === 'multi_select'">
                                            <select :name="`product_attributes[existing_${i}][attribute_value_id]`"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                <option value="">— Choose —</option>
                                                <template x-for="opt in row.values" :key="opt.id">
                                                    <option :value="opt.id" x-text="opt.label"></option>
                                                </template>
                                            </select>
                                        </template>
                                        {{-- Boolean --}}
                                        <template x-if="row.type === 'boolean'">
                                            <select :name="`product_attributes[existing_${i}][value_boolean]`"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                <option value="">— Choose —</option>
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        </template>
                                        {{-- Number --}}
                                        <template x-if="row.type === 'number'">
                                            <input type="number" step="any" :name="`product_attributes[existing_${i}][value_number]`"
                                                placeholder="Enter numeric value"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </template>
                                        {{-- Measurement --}}
                                        <template x-if="row.type === 'measurement'">
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="any" :name="`product_attributes[existing_${i}][value_number]`"
                                                    placeholder="Value"
                                                    class="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                                <input type="text" :name="`product_attributes[existing_${i}][value_text]`"
                                                    placeholder="Unit (e.g. MPa)"
                                                    class="w-24 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                            </div>
                                        </template>
                                        {{-- Text (default) --}}
                                        <template x-if="row.type === 'text'">
                                            <input type="text" :name="`product_attributes[existing_${i}][value_text]`"
                                                placeholder="Enter text value"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </template>
                                    </div>
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-300" x-text="row.type.replace(/_/g, ' ')"></span>
                                    <button type="button" @click="rows.splice(i, 1)"
                                        class="p-1 text-slate-400 hover:text-rose-500 transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    @endif
                </div>

                {{-- SECTION 5B: Warehouse Inventory Stock (If existing product) --}}
                @if($product)
                    <div id="section-inventory" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4 transition-colors">
                        <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>Live Warehouse Stock Levels</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Real-time inventory available across distribution hubs.</p>
                            </div>
                            <a href="{{ route('admin.inventory') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                <span>Inventory Operations Console →</span>
                            </a>
                        </div>

                        <div class="space-y-3">
                            @foreach($product->variants as $variant)
                                <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3.5 border border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200">{{ $variant->name }}</span>
                                            <span class="text-[11px] font-mono text-slate-500">({{ $variant->sku }})</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $variant->available_stock > 20 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : ($variant->available_stock > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300') }}">
                                            {{ $variant->available_stock }} {{ $variant->unit }}s Available
                                        </span>
                                    </div>

                                    @if($variant->inventoryItems->isNotEmpty())
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
                                            @foreach($variant->inventoryItems as $inv)
                                                <div class="bg-white dark:bg-slate-850 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs">
                                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                                        {{ $inv->warehouse->name ?? 'Warehouse' }}
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1 text-xs">
                                                        <span class="text-slate-600 dark:text-slate-400">On Hand: <strong class="text-slate-900 dark:text-white font-mono">{{ $inv->on_hand }}</strong></span>
                                                        <span class="text-slate-600 dark:text-slate-400">Reserved: <strong class="text-slate-900 dark:text-white font-mono">{{ $inv->reserved }}</strong></span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-1 text-[11px]">
                                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">Avail: {{ $inv->available }}</span>
                                                        <span class="text-slate-400">Min: {{ $inv->reorder_level }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-400 italic">No warehouse inventory records initialized for this variant.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- SECTION 6: Technical Documents (PDFs, SDS, Certificates) --}}
                <div id="section-documents" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4 transition-colors">
                    <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Technical Documents &amp; Certificates</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">BIS certificates, test reports, material safety data sheets (MSDS/SDS), and product brochures.</p>
                        </div>
                    </div>

                    @if($product && $product->documents->isNotEmpty())
                        <div class="space-y-2">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Attached Documents:</div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                @foreach($product->documents as $doc)
                                    <div class="p-3 flex items-center justify-between bg-white dark:bg-slate-900 text-xs">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs uppercase">
                                                PDF
                                            </div>
                                            <div>
                                                <a href="{{ $doc->url }}" target="_blank" class="font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 underline">
                                                    {{ $doc->title }}
                                                </a>
                                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $doc->file_type }}</div>
                                            </div>
                                        </div>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-rose-600 text-xs font-semibold">
                                            <input type="checkbox" name="delete_document_ids[]" value="{{ $doc->id }}"
                                                class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                            <span>Delete</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Upload New Technical Document:</div>
                        <div id="new-documents-container" class="space-y-2.5">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center doc-upload-row">
                                <div class="sm:col-span-5">
                                    <input type="text" name="document_titles[]" placeholder="Document Title (e.g. BIS IS:1489 Certificate)"
                                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                                </div>
                                <div class="sm:col-span-6">
                                    <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx"
                                        class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950 file:text-indigo-600 dark:file:text-indigo-400 hover:file:bg-indigo-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 7: Audit History & Change Log --}}
                @if($product && isset($recentAudits) && $recentAudits->isNotEmpty())
                    <div id="section-audit" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4 transition-colors">
                        <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>Recent Revision History &amp; Price Audits</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Automated trail of pricing and catalog changes recorded by AuditService.</p>
                            </div>
                        </div>

                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($recentAudits as $aIdx => $audit)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(! $loop->last)
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-800" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center ring-4 ring-white dark:ring-slate-950 text-xs font-bold">
                                                        ⚡
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5 text-xs">
                                                    <div>
                                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ ucfirst(str_replace('.', ' ', $audit->action)) }}</span>
                                                        <span class="text-slate-500">by {{ $audit->user?->name ?? 'System' }}</span>

                                                        @if($audit->action === 'price.changed')
                                                            <div class="mt-1 text-[11px] font-mono text-slate-600 dark:text-slate-300">
                                                                MRP: ₹{{ number_format(($audit->old_values['mrp'] ?? 0) / 100, 2) }} → ₹{{ number_format(($audit->new_values['mrp'] ?? 0) / 100, 2) }} |
                                                                Sell: ₹{{ number_format(($audit->old_values['selling_price'] ?? 0) / 100, 2) }} → ₹{{ number_format(($audit->new_values['selling_price'] ?? 0) / 100, 2) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-[11px] text-slate-400">
                                                        {{ $audit->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Sidebar Column (4 cols) --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- Status & Visibility --}}
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Publishing Status</h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="status" required
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="published" @selected(old('status', $product?->status ?? 'draft') === 'published')>Published (Live in Storefront)</option>
                            <option value="draft" @selected(old('status', $product?->status ?? 'draft') === 'draft')>Draft (Hidden from Customers)</option>
                            <option value="archived" @selected(old('status', $product?->status ?? 'draft') === 'archived')>Archived (Decommissioned)</option>
                        </select>
                    </div>

                    @if($product)
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-900 text-[11px] text-slate-500 space-y-1">
                            <div>Published: <strong class="text-slate-700 dark:text-slate-300">{{ $product->published_at ? $product->published_at->format('M d, Y H:i') : 'Not published' }}</strong></div>
                            <div>Last Modified: <strong class="text-slate-700 dark:text-slate-300">{{ $product->updated_at->diffForHumans() }}</strong></div>
                        </div>
                    @endif
                </div>

                {{-- Organization / Taxonomy --}}
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Catalog Taxonomy</h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Primary Category <span class="text-rose-500">*</span></label>
                        <select name="primary_category_id" required
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="">Select primary category…</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('primary_category_id', $product?->primary_category_id) == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Brand / Manufacturer</label>
                        <select name="brand_id"
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="">No brand specified</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Secondary Categories</label>
                        @php
                            $selectedCategoryIds = old('category_ids', $product?->categories->pluck('id')->toArray() ?? []);
                        @endphp
                        <div class="p-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-h-36 overflow-y-auto space-y-1.5">
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                                        @checked(in_array($cat->id, $selectedCategoryIds))
                                        class="rounded border-slate-400 text-indigo-600 focus:ring-indigo-500">
                                    <span>{{ $cat->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Search Tags</h3>
                        <a href="{{ route('admin.catalog.tags.index') }}" class="text-[11px] text-indigo-500 hover:underline">Manage Tags →</a>
                    </div>

                    <div class="flex flex-wrap gap-1.5 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-h-36 overflow-y-auto">
                        @foreach($tags as $tag)
                            <label class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer text-xs">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                    @checked(in_array($tag->id, old('tags', $product?->tags->pluck('id')->toArray() ?? [])))
                                    class="rounded border-slate-400 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                        @if($tags->isEmpty())
                            <span class="text-xs text-slate-400">No tags configured.</span>
                        @endif
                    </div>
                </div>

                {{-- SEO Metadata & Live Google Search Preview --}}
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">SEO &amp; Search Snippet</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">SEO Title</label>
                            <input type="text" name="seo_title" x-model="seoTitle" value="{{ old('seo_title', $product?->seo_title) }}"
                                placeholder="Custom browser & search engine title…"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Meta Description</label>
                            <textarea name="seo_description" rows="2" x-model="seoDescription"
                                placeholder="Search snippet description…"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('seo_description', $product?->seo_description) }}</textarea>
                        </div>

                        {{-- Live Google Search Result Preview Box --}}
                        <div class="mt-3 p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Search Preview</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate hover:underline cursor-pointer"
                                 x-text="seoTitle ? seoTitle : (name ? name + ' — UniversalEcom' : 'Product Title — UniversalEcom')"></div>
                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 truncate">
                                https://store.example.com › product › <span x-text="slug ? slug : 'product-slug'"></span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2"
                                 x-text="seoDescription ? seoDescription : 'Product description as shown in Google and Bing search engine results.'"></div>
                        </div>
                    </div>
                </div>

                {{-- Action Box --}}
                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200/60 dark:border-indigo-800/60 rounded-2xl p-5 space-y-3">
                    <button type="submit" name="action" value="save"
                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ $product ? 'Save Changes' : 'Create Product' }}</span>
                    </button>

                    @if($product)
                        <button type="submit" name="action" value="save_and_continue"
                            class="w-full py-2 px-4 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-850 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl border border-slate-200 dark:border-slate-700 transition">
                            Save &amp; Stay on Page
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </form>
</div>

{{-- Media Library Picker Modal --}}
<div id="media-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-3xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-slate-900 dark:text-white text-sm">Media Asset Library</h4>
                <p class="text-xs text-slate-500">Click any image to attach it to this product.</p>
            </div>
            <button type="button" onclick="closeMediaModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">✕</button>
        </div>

        <div class="p-3 border-b border-slate-100 dark:border-slate-900 bg-slate-50 dark:bg-slate-900/50 flex gap-2">
            <input type="text" id="modal-search" placeholder="Search assets…" onkeyup="searchMediaAssets(event)"
                class="flex-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button type="button" onclick="loadMediaAssets()" class="px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700">Search</button>
        </div>

        <div id="modal-assets-grid" class="p-4 overflow-y-auto flex-1 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 min-h-[300px]">
            <div class="col-span-full py-12 text-center text-slate-400 text-xs">Loading media assets…</div>
        </div>

        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
            <span class="text-xs text-slate-500" id="modal-selected-count">0 items selected</span>
            <button type="button" onclick="closeMediaModal()" class="px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-500 transition">Done</button>
        </div>
    </div>
</div>

<script>
// Attribute Spec Section
function attributeSection() {
    return {
        selectedDefId: '',
        rows: [],
        _counter: 0,
        addAttribute() {
            if (!this.selectedDefId) { return; }
            const select = this.$el.querySelector(`select[x-model="selectedDefId"]`);
            const opt = select.querySelector(`option[value="${this.selectedDefId}"]`);
            if (!opt) { return; }
            this.rows.push({
                tempId: ++this._counter,
                defId: this.selectedDefId,
                name: opt.dataset.name,
                type: opt.dataset.type,
                values: JSON.parse(opt.dataset.values || '[]'),
            });
            this.selectedDefId = '';
        }
    };
}

// Local File Select Previews
function handleImageSelect(event) {
    const files = event.target.files;
    const container = document.getElementById('selected-previews');
    container.innerHTML = '';
    if (!files.length) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');

    Array.from(files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'relative aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <span class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-900/80 text-white truncate max-w-[90%]">${file.name}</span>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// Media Library Picker Modal Logic
let selectedLibraryAssets = new Map();

function openMediaModal() {
    document.getElementById('media-modal').classList.remove('hidden');
    loadMediaAssets();
}

function closeMediaModal() {
    document.getElementById('media-modal').classList.add('hidden');
    renderSelectedLibraryAssets();
}

function searchMediaAssets(e) {
    if (e.key === 'Enter') {
        loadMediaAssets();
    }
}

function loadMediaAssets() {
    const query = document.getElementById('modal-search').value;
    const grid = document.getElementById('modal-assets-grid');
    grid.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400 text-xs">Loading media assets…</div>';

    fetch(`{{ route('admin.media.api') }}?only_images=1&search=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data || data;
            if (!items.length) {
                grid.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400 text-xs">No images found in library. Upload files in the Media Assets manager.</div>';
                return;
            }
            grid.innerHTML = '';
            items.forEach(asset => {
                const isSelected = selectedLibraryAssets.has(asset.id);
                const card = document.createElement('div');
                card.id = `modal-asset-${asset.id}`;
                card.className = `cursor-pointer relative aspect-square rounded-xl overflow-hidden border transition ${isSelected ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-slate-200 dark:border-slate-800 hover:border-slate-400'}`;
                card.onclick = () => toggleLibraryAsset(asset);
                card.innerHTML = `
                    <img src="${asset.url}" alt="${asset.name}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-indigo-900/20 ${isSelected ? '' : 'hidden'}" id="check-overlay-${asset.id}">
                        <span class="absolute top-1 right-1 w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold">✓</span>
                    </div>
                    <span class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded text-[8px] font-bold bg-slate-900/80 text-white truncate max-w-[90%]">${asset.name}</span>
                `;
                grid.appendChild(card);
            });
        })
        .catch(() => {
            grid.innerHTML = '<div class="col-span-full py-12 text-center text-rose-500 text-xs">Failed to load media assets.</div>';
        });
}

function toggleLibraryAsset(asset) {
    const overlay = document.getElementById(`check-overlay-${asset.id}`);
    const card = document.getElementById(`modal-asset-${asset.id}`);
    if (selectedLibraryAssets.has(asset.id)) {
        selectedLibraryAssets.delete(asset.id);
        if (overlay) overlay.classList.add('hidden');
        if (card) {
            card.classList.remove('ring-2', 'ring-indigo-500', 'border-indigo-500');
            card.classList.add('border-slate-200', 'dark:border-slate-800');
        }
    } else {
        selectedLibraryAssets.set(asset.id, asset);
        if (overlay) overlay.classList.remove('hidden');
        if (card) {
            card.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500');
            card.classList.remove('border-slate-200', 'dark:border-slate-800');
        }
    }
    document.getElementById('modal-selected-count').textContent = `${selectedLibraryAssets.size} items selected`;
}

function renderSelectedLibraryAssets() {
    const container = document.getElementById('library-assets-container');
    const grid = document.getElementById('library-assets-grid');
    grid.innerHTML = '';

    if (!selectedLibraryAssets.size) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');

    selectedLibraryAssets.forEach((asset, id) => {
        const item = document.createElement('div');
        item.className = 'relative aspect-square rounded-xl overflow-hidden border border-indigo-400 bg-slate-50 dark:bg-slate-900 p-1 group';
        item.innerHTML = `
            <img src="${asset.url}" class="w-full h-full object-cover rounded-lg">
            <input type="hidden" name="media_asset_ids[]" value="${id}">
            <button type="button" onclick="removeLibrarySelection(${id})" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white text-xs flex items-center justify-center font-bold opacity-80 hover:opacity-100">✕</button>
        `;
        grid.appendChild(item);
    });
}

function removeLibrarySelection(id) {
    selectedLibraryAssets.delete(id);
    renderSelectedLibraryAssets();
}

let variantCount = {{ ($product && $product->variants->isNotEmpty()) ? $product->variants->count() + 10 : 2 }};

function addVariant() {
    const container = document.getElementById('variants-container');
    const idx = variantCount++;
    const row = document.createElement('div');
    row.className = 'variant-row bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3.5 relative transition-all';
    row.setAttribute('x-data', '{ mrp: 0, selling: 0, get discount() { if (this.mrp > 0 && this.mrp >= this.selling) { return Math.round(((this.mrp - this.selling) / this.mrp) * 100); } return 0; }, get isLoss() { return this.mrp > 0 && this.selling > this.mrp; } }');

    row.innerHTML = `
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-2">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-black text-xs flex items-center justify-center">
                    #${idx + 1}
                </span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">New Variant</span>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="discount > 0 && !isLoss" x-cloak class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <span x-text="discount"></span>% OFF MRP
                </span>
                <span x-show="isLoss" x-cloak class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                    ⚠ Selling Price exceeds MRP
                </span>
                <button type="button" onclick="removeVariantRow(this)" title="Remove row" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-4">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Variant Title *</label>
                <input type="text" name="variants[${idx}][name]" required placeholder="e.g. 50 KG Sealed Bag"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">SKU *</label>
                <input type="text" name="variants[${idx}][sku]" required placeholder="SKU-NEW-${idx}"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">MRP (₹) *</label>
                <input type="number" step="0.01" min="0" name="variants[${idx}][mrp]" required x-model.number="mrp" placeholder="480.00"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Selling Price (₹) *</label>
                <input type="number" step="0.01" min="0" name="variants[${idx}][selling_price]" required x-model.number="selling" placeholder="450.00"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 pt-1">
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Unit *</label>
                <input type="text" name="variants[${idx}][unit]" required value="piece" placeholder="bag, kg…"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Pack Size Label</label>
                <input type="text" name="variants[${idx}][pack_size]" placeholder="e.g. 50 KG Bag"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Weight (kg)</label>
                <input type="number" step="0.001" min="0" name="variants[${idx}][weight_kg]" placeholder="1.0"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500 font-mono">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Tax Bracket</label>
                <select name="variants[${idx}][tax_class_id]"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                    <option value="">No Tax Class (Default)</option>
                    @foreach($taxClasses as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->name }} ({{ $tc->rate_percentage }}%)</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">Status</label>
                <select name="variants[${idx}][status]"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>
    `;
    container.appendChild(row);
}

function removeVariantRow(btn) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length <= 1) {
        alert('A product must retain at least one variant.');
        return;
    }
    btn.closest('.variant-row').remove();
}

function removeExistingVariant(btn, variantId) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length <= 1) {
        alert('A product must retain at least one variant.');
        return;
    }
    if (confirm('Are you sure you want to remove this variant? If it is linked to order history or inventory batches, it will be safely archived.')) {
        const delContainer = document.getElementById('deleted-variants-container');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_variant_ids[]';
        input.value = variantId;
        delContainer.appendChild(input);
        btn.closest('.variant-row').remove();
    }
}

let tierIndex = 50;
function addTierRow() {
    const tbody = document.getElementById('tiers-table-body');
    const idx = tierIndex++;
    const tr = document.createElement('tr');
    tr.className = 'tier-row hover:bg-slate-50/50 dark:hover:bg-slate-900/30';
    tr.innerHTML = `
        <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300 text-xs">
            Primary Variant
        </td>
        <td class="py-2.5 px-3">
            <input type="number" min="1" name="variants[0][tiers][${idx}][min_quantity]" value="10" required
                class="w-24 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs font-mono">
        </td>
        <td class="py-2.5 px-3">
            <input type="number" min="1" name="variants[0][tiers][${idx}][max_quantity]" placeholder="No limit (+)"
                class="w-24 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs font-mono">
        </td>
        <td class="py-2.5 px-3">
            <div class="relative w-32">
                <span class="absolute left-2.5 top-1.5 text-slate-400 text-xs">₹</span>
                <input type="number" step="0.01" min="0" name="variants[0][tiers][${idx}][unit_price]" required placeholder="420.00"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-6 pr-2 py-1 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
            </div>
        </td>
        <td class="py-2.5 px-3 text-right">
            <button type="button" onclick="this.closest('.tier-row').remove()" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection
