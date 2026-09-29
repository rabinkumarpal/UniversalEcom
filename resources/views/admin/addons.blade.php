@extends('layouts.admin')

@section('title', 'Add-on Registry')
@section('header_title', 'Portable Add-ons & Domain Packs Registry')
@section('header_subtitle', 'Modular architecture extensions with isolated migrations, services, and domain contracts')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
            <span>✓ {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs flex items-center justify-between">
            <span>⚠ {{ session('error') }}</span>
        </div>
    @endif

    <!-- Architectural Architecture Banner -->
    <div class="bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-2xl p-6 transition-colors">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-md">
                🧩
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Universal Core & Modular Monolith Isolation</h2>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                    The platform core provides domain-neutral capabilities (Identity, RBAC, Catalog, Pricing Pipeline, Concurrency-Safe Stock, Checkout, Order State Machine). Every vertical and complex capability is implemented as a self-contained, portable add-on package in <code class="font-mono text-indigo-700 dark:text-indigo-300 text-[11px] bg-indigo-100/70 dark:bg-slate-900 px-1.5 py-0.5 rounded">packages/</code> with its own isolated migrations, domain models, and service providers.
                </p>
            </div>
        </div>
    </div>

    <!-- Add-ons Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($addons as $addon)
            @php
                $details = match($addon->id()) {
                    'vendor-marketplace' => [
                        'type' => 'Capability Add-on',
                        'package' => 'packages/vendor-marketplace',
                        'tables' => ['vendors', 'vendor_users', 'vendor_offers', 'vendor_inventory', 'vendor_orders', 'vendor_order_items', 'vendor_payouts'],
                        'features' => ['Multi-vendor carts', 'Order splitting on OrderPlaced', 'Commission calculations', 'Vendor portal API'],
                        'color' => 'indigo',
                    ],
                    'promotion-engine' => [
                        'type' => 'Capability Add-on',
                        'package' => 'packages/promotion-engine',
                        'tables' => ['promotions', 'promotion_rules', 'promotion_rewards', 'promotion_usages', 'coupons'],
                        'features' => ['Tiered volume pricing', 'BOGO & Bundles', 'Cart spending goals', 'Coupons & Free delivery'],
                        'color' => 'emerald',
                    ],
                    'domain-construction' => [
                        'type' => 'Reference Domain Pack',
                        'package' => 'packages/domain-construction',
                        'tables' => ['Dynamic Attributes (EAV)', 'Civil/Steel/Sanitary Categories', 'Bulk Tier Matrices'],
                        'features' => ['Construction reference seeders', 'Weight & Unit variations', 'Site delivery workflows'],
                        'color' => 'amber',
                    ],
                    'loyalty-wallet' => [
                        'type' => 'Retention & Loyalty Add-on',
                        'package' => 'packages/loyalty-wallet',
                        'tables' => ['wallet_accounts', 'wallet_ledger_entries', 'product_reviews', 'wishlists'],
                        'features' => ['Append-only wallet ledger', 'Wallet checkout gateway', '2% Loyalty cashback', 'Verified buyer reviews', '1-Click reorder'],
                        'color' => 'teal',
                    ],
                    default => [
                        'type' => 'Portable Extension',
                        'package' => 'packages/' . $addon->id(),
                        'tables' => ['Isolated Package Tables'],
                        'features' => ['Custom Domain Capabilities'],
                        'color' => 'slate',
                    ]
                };
            @endphp

            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex flex-col justify-between shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400">
                                {{ $details['type'] }}
                            </span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mt-2">{{ $addon->name() }}</h3>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold font-mono bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 rounded-md">
                            v{{ $addon->version() }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        {{ $addon->description() }}
                    </p>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-900 space-y-2 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Package Path</span>
                            <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ $details['package'] }}</span>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Isolated Tables & Entities</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($details['tables'] as $tbl)
                                    <span class="font-mono text-[10px] bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-800">{{ $tbl }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Key Capabilities</span>
                            <ul class="list-disc pl-4 text-[11px] text-slate-600 dark:text-slate-400 space-y-0.5 mt-1">
                                @foreach($details['features'] as $feat)
                                    <li>{{ $feat }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                @php
                    $isAddonEnabled = app(\App\Core\Services\SettingService::class)->isAddonEnabled($addon->id());
                @endphp

                <div class="pt-5 mt-5 border-t border-slate-100 dark:border-slate-900 flex items-center justify-between">
                    @if($isAddonEnabled)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            Active &amp; Booted
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 dark:text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                            Disabled
                        </span>
                    @endif

                    <form method="POST" action="{{ route('admin.addons.toggle', $addon->id()) }}" class="inline">
                        @csrf
                        @if($isAddonEnabled)
                            <button type="submit" class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 hover:bg-rose-50 dark:bg-slate-900 dark:hover:bg-rose-950 text-slate-700 hover:text-rose-700 dark:text-slate-400 dark:hover:text-rose-300 border border-slate-200 hover:border-rose-300 dark:border-slate-800 dark:hover:border-rose-800 transition">
                                Disable
                            </button>
                        @else
                            <button type="submit" class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 transition">
                                Enable
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 text-slate-500 text-xs">
                No portable add-ons currently registered.
            </div>
        @endforelse
    </div>

    <!-- Developer Extensibility Guide Card -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4 shadow-xs transition-colors">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">How to Add New Domain Packs or Add-ons</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            The platform is structured for zero-modification expansion. To deploy a new domain vertical (e.g. <em>Fashion &amp; Apparel</em>, <em>Consumer Electronics</em>, or <em>Grocery</em>), or add a new capability (e.g. <em>Loyalty Wallet</em>):
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2">
                <span class="font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400">01 / Create Package</span>
                <p class="text-slate-600 dark:text-slate-300 text-[11px]">Create a directory in <code class="font-mono text-slate-900 dark:text-white">packages/my-extension</code> with <code class="font-mono text-slate-900 dark:text-white">composer.json</code>, migrations, and domain services.</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2">
                <span class="font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400">02 / Implement Contract</span>
                <p class="text-slate-600 dark:text-slate-300 text-[11px]">Implement <code class="font-mono text-slate-900 dark:text-white">AddonInterface</code> or core contracts (<code class="font-mono text-slate-900 dark:text-white">PricingAdjustmentContract</code>, <code class="font-mono text-slate-900 dark:text-white">PaymentGatewayContract</code>).</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2">
                <span class="font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400">03 / Register in Bootstrap</span>
                <p class="text-slate-600 dark:text-slate-300 text-[11px]">Register the Service Provider in <code class="font-mono text-slate-900 dark:text-white">bootstrap/providers.php</code> and PSR-4 path in <code class="font-mono text-slate-900 dark:text-white">composer.json</code>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
