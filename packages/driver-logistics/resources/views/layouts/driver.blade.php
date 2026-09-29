<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Driver Logistics Portal') — Fleet Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 selection:bg-amber-500 selection:text-slate-950">
    <div class="min-h-full max-w-md mx-auto flex flex-col bg-slate-900 border-x border-slate-800 shadow-2xl relative">
        <!-- Top App Bar -->
        <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 font-black text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h1 class="text-sm font-black tracking-tight text-white leading-tight">FLEET POD</h1>
                    <p class="text-[10px] text-slate-400 font-mono">Driver Portal</p>
                </div>
            </div>

            @auth
                <div class="flex items-center gap-2">
                    @if(isset($driver))
                        <form method="POST" action="{{ route('driver.status.toggle') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-bold transition flex items-center gap-1.5 border {{ $driver->status === 'active' ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-300 hover:bg-emerald-500/30' : 'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700' }}" title="Toggle On/Off Duty">
                                <span class="w-1.5 h-1.5 rounded-full {{ $driver->status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' }}"></span>
                                {{ $driver->status === 'active' ? 'ON DUTY' : 'OFF DUTY' }}
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('driver.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition" title="Log Out">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            @endauth
        </header>

        <!-- Flash Notifications -->
        <div class="px-4 pt-3 space-y-2">
            @if(session('success'))
                <div class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-3 rounded-xl bg-amber-950/80 border border-amber-800 text-amber-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-semibold space-y-1">
                    @if(session('error'))
                        <div>{{ session('error') }}</div>
                    @endif
                    @foreach($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 pb-24">
            @yield('content')
        </main>

        @auth
            <!-- Mobile Bottom Nav Bar -->
            <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-4 py-2 flex items-center justify-around shadow-lg">
                <a href="{{ route('driver.dashboard') }}" class="flex flex-col items-center gap-1 text-[11px] font-bold transition {{ request()->routeIs('driver.dashboard') ? 'text-amber-400' : 'text-slate-400 hover:text-slate-200' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>Manifest</span>
                </a>

                <a href="{{ route('driver.dashboard') }}#active-shipments" class="flex flex-col items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    <span>Active Drops</span>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-slate-200 transition" target="_blank">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Central Admin</span>
                </a>
            </nav>
        @endauth
    </div>

    @stack('scripts')
</body>
</html>
