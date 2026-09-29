<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Application & Registration - Universal Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-800 antialiased">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 text-center text-white">
            <div class="inline-flex p-3 bg-amber-500 text-slate-950 rounded-xl mb-3 shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h1 class="text-xl font-black">Vendor Partnership Application</h1>
            <p class="text-xs text-slate-400 mt-1">Join our multi-category commerce marketplace</p>
        </div>

        <div class="p-6">
            @if($errors->any())
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs font-semibold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.register') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Company Legal Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" required
                               placeholder="e.g. Apex Hardware Pvt Ltd"
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Public Display Name</label>
                        <input type="text" name="display_name" value="{{ old('display_name') }}" required
                               placeholder="e.g. Apex Hardware"
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Authorized Contact Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="e.g. Rahul Sharma"
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               placeholder="+91 98765 43210"
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="partner@company.com"
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" required
                               placeholder="Minimum 8 characters"
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-sm shadow-md transition-all">
                        Submit Vendor Application
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500">
                    Already have a vendor partner account?
                    <a href="{{ route('vendor.login') }}" class="font-bold text-amber-600 hover:text-amber-700 underline ml-1">
                        Sign In
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
