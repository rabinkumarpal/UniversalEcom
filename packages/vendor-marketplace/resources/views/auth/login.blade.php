<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Partner Login - Universal Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-800 antialiased">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 text-center text-white">
            <div class="inline-flex p-3 bg-amber-500 text-slate-950 rounded-xl mb-3 shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h1 class="text-xl font-black">Vendor Central Login</h1>
            <p class="text-xs text-slate-400 mt-1">Multi-Tenant Merchant & Fulfillment Portal</p>
        </div>

        <div class="p-6">
            @if(session('status'))
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs font-semibold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Pre-configured Demo Credentials -->
            <div class="mb-4 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-amber-950 uppercase tracking-wider text-[10px] flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Pre-Seeded Demo Merchant
                    </span>
                    <button type="button" onclick="document.querySelector('[name=email]').value='vendor@southernsteel.test'; document.querySelector('[name=password]').value='password';" class="text-[11px] font-black text-amber-700 hover:text-amber-900 underline">
                        Auto-Fill
                    </button>
                </div>
                <div class="text-amber-900 text-[11px] flex justify-between font-mono">
                    <span>Email: <strong>vendor@southernsteel.test</strong></span>
                    <span>Pass: <strong>password</strong></span>
                </div>
            </div>

            <form method="POST" action="{{ route('vendor.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-shadow">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-shadow">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-sm shadow-md transition-all">
                    Sign In to Vendor Portal
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500">
                    Want to sell on Universal Commerce?
                    <a href="{{ route('vendor.register') }}" class="font-bold text-amber-600 hover:text-amber-700 underline ml-1">
                        Register as a Vendor
                    </a>
                </p>
                <a href="{{ url('/') }}" class="inline-block mt-3 text-xs text-slate-400 hover:text-slate-600">
                    &larr; Back to Storefront
                </a>
            </div>
        </div>
    </div>
</body>
</html>
