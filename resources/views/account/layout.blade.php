@extends('layouts.storefront')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col md:flex-row gap-8 items-start">
        <!-- Account Sidebar -->
        <aside class="w-full md:w-64 bg-white rounded-2xl border border-slate-200 p-5 shadow-xs shrink-0">
            <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    {{ substr(auth()->user()?->name ?? 'C', 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()?->name ?? 'Contractor Account' }}</div>
                    <div class="text-[11px] text-slate-500 truncate">{{ auth()->user()?->email ?? 'customer@example.com' }}</div>
                </div>
            </div>

            <nav class="mt-4 space-y-1 text-xs font-semibold">
                <a href="{{ route('account.orders') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('account.orders*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>My Orders & Reorders</span>
                </a>

                @php
                    $b2bUser = auth()->user();
                    $b2bCompanyUser = $b2bUser && class_exists(\Packages\B2BCommerce\Models\CompanyUser::class)
                        ? \Packages\B2BCommerce\Models\CompanyUser::where('user_id', $b2bUser->id)->where('is_active', true)->with('company')->first()
                        : null;
                    $b2bCompany = $b2bCompanyUser?->company;
                @endphp

                @if($b2bCompany && $b2bCompany->isActive())
                    <a href="{{ route('account.b2b.index') }}" class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('account.b2b*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Corporate Account</span>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">
                            {{ $b2bCompanyUser->role }}
                        </span>
                    </a>
                @endif

                <a href="{{ route('account.wallet') }}" class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('account.wallet*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span>Digital Wallet</span>
                    </div>
                    @php
                        $user = auth()->user();
                        $userWallet = $user ? app(\Packages\LoyaltyWallet\Services\WalletService::class)->getOrCreateWallet($user) : null;
                    @endphp
                    @if($userWallet)
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                            ₹{{ number_format($userWallet->balance / 100, 2) }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('account.wishlist') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('account.wishlist*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span>Saved Wishlist</span>
                </a>

                <a href="{{ route('account.addresses') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('account.addresses*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Delivery Addresses &amp; Sites</span>
                </a>

                <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('account.profile*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Profile &amp; Credentials</span>
                </a>
            </nav>
        </aside>

        <!-- Main Account Content -->
        <main class="flex-1 min-w-0 w-full">
            @yield('account_content')
        </main>
    </div>
</div>
@endsection
