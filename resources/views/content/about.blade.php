@extends('layouts.storefront')

@section('title', 'About Us — Universal Ecommerce Architecture')
@section('meta_description', 'Learn about our universal, multi-category commerce platform built on Laravel 13, featuring server-authoritative pricing, pessimistic stock safety, and portable add-ons.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">About Universal Commerce</span>
    </nav>

    <div class="text-center max-w-2xl mx-auto">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Architectural Overview</span>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">The Modern Universal Commerce Engine</h1>
        <p class="text-sm text-slate-500 mt-3 leading-relaxed">
            Engineered from first principles to power high-volume, multi-tenant physical commerce with zero compromise on pricing authority, inventory integrity, or extensible architecture.
        </p>
    </div>

    <!-- Core Pillars Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
                1
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Domain-Neutral Core</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Core tables contain zero domain-specific columns. An EAV + JSON hybrid architecture dynamically adapts to electronics, fashion, grocery, or heavy industrial building supplies.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black">
                2
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Pessimistic Inventory Safety</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Row-locking (<code class="text-[11px] font-mono bg-slate-100 px-1 py-0.5 rounded">lockForUpdate</code>) stock reservations and immutable movement logs prevent overselling under high concurrency flash sales or bulk orders.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
                3
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Server-Authoritative Pricing</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                A 5-stage pricing pipeline validates base selling rates, bulk quantity tier matrices, promotions, tax classes, and delivery fees server-side without trusting client input.
            </p>
        </div>
    </div>

    <!-- Reference Implementation Callout -->
    <div class="bg-slate-950 text-slate-300 p-8 rounded-2xl space-y-4">
        <span class="px-2.5 py-1 rounded bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold uppercase tracking-wider">
            Reference Vertical
        </span>
        <h3 class="text-xl font-bold text-white">Construction & Heavy Industrial Supplies</h3>
        <p class="text-xs leading-relaxed text-slate-400">
            The platform uses construction materials as its first reference domain pack (<code class="text-[11px] font-mono text-slate-200">packages/domain-construction</code>) to rigorously validate the hardest ecommerce constraints: bulk quantity discounts, physical packaging units (bags, tonnes, pieces), dynamic technical test certificates, and local delivery zones.
        </p>
    </div>
</div>
@endsection
