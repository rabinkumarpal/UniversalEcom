<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations Console Login - Universal Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-100 antialiased font-sans">
    <div class="w-full max-w-md bg-slate-900 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 p-6 text-center border-b border-slate-800">
            <div class="inline-flex p-3 bg-indigo-600 text-white rounded-xl mb-3 shadow-lg shadow-indigo-600/30">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-black text-white tracking-tight">UniversalEcom Operations</h1>
            <p class="text-xs text-indigo-400 font-semibold mt-1 uppercase tracking-wider">Administrative Control Console</p>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="mb-4 p-3 bg-emerald-950/60 border border-emerald-800/60 rounded-xl text-xs font-semibold text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('status'))
                <div class="mb-4 p-3 bg-indigo-950/60 border border-indigo-800/60 rounded-xl text-xs font-semibold text-indigo-300">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-rose-950/60 border border-rose-800/60 rounded-xl text-xs font-semibold text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Pre-configured Demo Credentials -->
            <div class="mb-5 p-3.5 bg-slate-800/70 border border-slate-700/80 rounded-xl text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-300 uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Pre-Seeded System Admin
                    </span>
                    <button type="button" onclick="document.querySelector('[name=email]').value='admin@ecom-laravel.test'; document.querySelector('[name=password]').value='password';" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 underline cursor-pointer">
                        Auto-Fill
                    </button>
                </div>
                <div class="text-slate-400 text-[11px] flex justify-between font-mono">
                    <span>Email: <strong class="text-slate-200">admin@ecom-laravel.test</strong></span>
                    <span>Pass: <strong class="text-slate-200">password</strong></span>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Administrator Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800 text-white placeholder-slate-500 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Security Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800 text-white placeholder-slate-500 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-indigo-500">
                        Remember session
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                    Authenticate to Console &rarr;
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-800/80 text-center">
                <a href="{{ url('/') }}" class="inline-block text-xs text-slate-400 hover:text-slate-200 transition">
                    &larr; Back to Public Storefront
                </a>
            </div>
        </div>
    </div>
</body>
</html>
