<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vendor Partner Portal') - Universal Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800">
    <div class="min-h-full flex flex-col md:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 bg-slate-900 text-slate-100 flex-shrink-0 flex flex-col border-r border-slate-800">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs uppercase tracking-widest font-bold text-amber-400">Partner Central</span>
                    <h1 class="text-lg font-black tracking-tight text-white flex items-center gap-1.5">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Vendor Portal
                    </h1>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Active
                </span>
            </div>

            <!-- Current Vendor Info Banner -->
            @if(request()->attributes->get('vendor'))
                <div class="px-5 py-3 bg-slate-800/60 border-b border-slate-800">
                    <p class="text-xs text-slate-400 font-medium">Logged in as</p>
                    <p class="text-sm font-bold text-white truncate">{{ request()->attributes->get('vendor')->display_name }}</p>
                    <p class="text-xs text-slate-400 font-mono truncate">Comm: {{ request()->attributes->get('vendor')->commission_rate_percentage }}%</p>
                </div>
            @endif

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm font-medium">
                <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('vendor.dashboard') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>

                <a href="{{ route('vendor.offers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('vendor.offers.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Product Offers
                </a>

                <a href="{{ route('vendor.orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('vendor.orders.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Orders & Fulfillment
                </a>

                <a href="{{ route('vendor.payouts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('vendor.payouts.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Payouts & Settlement
                </a>

                <a href="{{ route('vendor.profile.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('vendor.profile.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Vendor Profile
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800 space-y-2">
                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Live Storefront
                </a>
                <form method="POST" action="{{ route('vendor.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 text-xs font-semibold text-rose-400 hover:text-rose-300 py-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm">
                <div>
                    <h2 class="text-xl font-black text-slate-900">@yield('title', 'Vendor Portal')</h2>
                    <p class="text-xs text-slate-500">@yield('subtitle', 'Manage your inventory, fulfillment, and payouts')</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('vendor.offers.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Offer
                    </a>
                </div>
            </header>

            <!-- Alerts -->
            <div class="p-6 pb-0">
                @if(session('success'))
                    <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
