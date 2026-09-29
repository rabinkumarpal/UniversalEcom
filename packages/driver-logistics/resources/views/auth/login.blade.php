@extends('driver-logistics::layouts.driver')

@section('title', 'Driver Login')

@section('content')
<div class="py-6 space-y-6">
    <!-- Header Hero -->
    <div class="text-center space-y-2">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
        </div>
        <h2 class="text-xl font-black text-white tracking-tight">Driver Fleet Portal</h2>
        <p class="text-xs text-slate-400">Sign in with your driver registered phone number or email to access your live delivery manifest.</p>
    </div>

    <!-- Login Card -->
    <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5 shadow-xl">
        <form method="POST" action="{{ route('driver.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Email or Phone Number
                </label>
                <div class="relative">
                    <input
                        type="text"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="driver@example.com or 9876543210"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition"
                    >
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Password / Driver PIN
                </label>
                <div class="relative">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition"
                    >
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-400">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0">
                    <span>Keep me logged in</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-black text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2 cursor-pointer"
            >
                <span>Start Shift &amp; Log In</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <!-- Quick Info / Support Notice -->
    <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/50 text-center space-y-1 text-xs text-slate-400">
        <p class="font-semibold text-slate-300">Need fleet dispatch assistance?</p>
        <p>Contact Central Logistics Ops at <a href="tel:+918001234567" class="text-amber-400 font-mono font-bold hover:underline">+91 800-123-4567</a></p>
    </div>
</div>
@endsection
