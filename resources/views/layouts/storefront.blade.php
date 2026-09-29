<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Universal Ecommerce Platform')</title>
    <meta name="description" content="@yield('meta_description', 'Universal enterprise ecommerce platform with transparent bulk tier pricing, local fulfillment and multi-vendor marketplace.')">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Open Graph Metadata -->
    <meta property="og:title" content="@yield('title', 'Universal Ecommerce Platform')">
    <meta property="og:description" content="@yield('meta_description', 'Universal enterprise ecommerce platform with transparent bulk tier pricing, local fulfillment and multi-vendor marketplace.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('schema')
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased font-sans">
    <!-- Top Reference Announcement Bar -->
    <div class="bg-amber-600 px-4 py-2 text-center text-xs font-semibold text-white shadow-inner flex items-center justify-center gap-2">
        <span class="inline-block px-2 py-0.5 bg-amber-800 text-amber-100 rounded text-[10px] tracking-wide uppercase">Core Architecture</span>
        <span>Universal Ecommerce Core • Reference Domain Pack: Construction Materials & Bulk Supplies</span>
        <a href="{{ route('admin.dashboard') }}" class="ml-3 underline hover:text-amber-200">Go to Admin Operations &rarr;</a>
    </div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs" x-data="{ locationModal: false, searchOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-6">
                    <a href="{{ route('storefront.home') }}" class="flex items-center gap-2 group">
                        <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-md group-hover:bg-indigo-700 transition">
                            U
                        </div>
                        <div>
                            <span class="text-xl font-extrabold tracking-tight text-slate-900 block leading-none">UniversalEcom</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Enterprise Platform</span>
                        </div>
                    </a>

                    <!-- Location Selector -->
                    <button @click="locationModal = true" type="button" class="hidden md:flex items-center gap-2 text-xs font-medium text-slate-700 hover:text-indigo-600 bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200 transition">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Deliver to: <strong class="text-slate-900">{{ session('delivery_pincode', '560001') }}</strong></span>
                    </button>
                </div>

                <!-- Live Search Bar -->
                <div class="flex-1 max-w-xl mx-4">
                    <form action="{{ route('storefront.catalog') }}" method="GET" class="relative">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search cement, TMT rebar, pipes, electrical, brands..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-100 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </form>
                </div>

                <!-- Right Actions: Catalog, Cart & Admin -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('storefront.catalog') }}" class="hidden sm:inline-flex text-sm font-semibold text-slate-700 hover:text-indigo-600">
                        Catalog
                    </a>

                    <a href="{{ route('content.calculators') }}" class="hidden md:inline-flex text-sm font-semibold text-slate-700 hover:text-indigo-600">
                        Calculators
                    </a>

                    <a href="{{ route('content.knowledge.index') }}" class="hidden md:inline-flex text-sm font-semibold text-slate-700 hover:text-indigo-600">
                        Knowledge
                    </a>

                    <a href="{{ route('account.wallet') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-indigo-600 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-200 transition">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span>Wallet</span>
                    </a>

                    <a href="{{ route('account.orders') }}" class="hidden sm:inline-flex text-sm font-semibold text-slate-700 hover:text-indigo-600">
                        My Orders
                    </a>

                    @php
                        $cart = app(\App\Domain\Cart\CartService::class)->getOrCreateCart(auth()->user(), session()->getId());
                        $itemCount = $cart->total_quantity;
                    @endphp

                    <!-- Cart Drawer Action -->
                    <a href="{{ route('storefront.cart') }}" class="relative flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3.5 py-2 rounded-lg text-sm font-semibold transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="hidden sm:inline">Cart</span>
                        @if($itemCount > 0)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                                {{ $itemCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        <!-- Location Modal -->
        <div x-show="locationModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div @click.away="locationModal = false" class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Select Delivery Location</h3>
                <p class="text-xs text-slate-500 mb-4">Enter your destination pincode to verify local slot delivery & bulk transport rates.</p>
                <form action="{{ route('storefront.set_location') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Serviceable Pincode</label>
                        <input type="text" name="pincode" value="{{ session('delivery_pincode', '560001') }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="locationModal = false" class="px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">Save Location</button>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <!-- Global Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="p-4 mb-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-4 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between">
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <!-- Main Page Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded bg-indigo-500 flex items-center justify-center text-white font-bold text-lg">U</div>
                    <span class="text-lg font-bold text-white">UniversalEcom</span>
                </div>
                <p class="text-xs leading-relaxed text-slate-400">
                    A clean, modular Laravel ecommerce engine powering domain-neutral commerce, portable add-ons, and reference industry packs.
                </p>
            </div>
            <div>
                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Knowledge & Tools</h4>
                <ul class="text-xs space-y-2">
                    <li><a href="{{ route('content.calculators') }}" class="hover:text-white">Material Calculators &rarr;</a></li>
                    <li><a href="{{ route('content.knowledge.index') }}" class="hover:text-white">Technical Guides &rarr;</a></li>
                    <li><a href="{{ route('content.faq') }}" class="hover:text-white">FAQ & Help &rarr;</a></li>
                    <li><a href="{{ route('content.about') }}" class="hover:text-white">About the Platform &rarr;</a></li>
                    <li><a href="{{ route('content.contact') }}" class="hover:text-white">Contact & Support &rarr;</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Active Portable Add-ons</h4>
                <ul class="text-xs space-y-2">
                    <li>Vendor Marketplace & Splitting</li>
                    <li>Advanced Promotion Engine</li>
                    <li>Construction Domain Pack</li>
                    <li>Local Delivery Zones</li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Portals & Ecosystem</h4>
                <ul class="text-xs space-y-2">
                    <li><a href="{{ route('vendor.dashboard') }}" class="text-amber-400 hover:text-amber-300 font-semibold">Vendor Central Portal &rarr;</a></li>
                    <li><a href="{{ route('admin.dashboard') }}" class="hover:text-white">Admin Operations Panel &rarr;</a></li>
                    <li><a href="{{ url('/api/v1/categories') }}" class="hover:text-white">API v1 Catalog &rarr;</a></li>
                    <li><a href="{{ route('sitemap') }}" target="_blank" class="hover:text-white">XML Sitemap &rarr;</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-8 border-t border-slate-800 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Universal Ecommerce Platform. Built on Laravel 13 & PHP 8.4.
        </div>
    </footer>
</body>
</html>
