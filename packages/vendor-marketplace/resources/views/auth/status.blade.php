<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Application Status - Universal Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-800 antialiased">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden text-center">
        <div class="p-8">
            @if($status === 'pending')
                <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h1 class="text-xl font-black text-slate-900">Application Under Review</h1>
                <p class="text-sm text-slate-600 mt-2">
                    Thank you for applying to sell on Universal Commerce, <strong class="text-slate-900">{{ $vendor->display_name }}</strong>.
                </p>
                <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed text-left">
                    {{ $message }} Our compliance team verifies your business details within 24-48 business hours. You will receive an email once approved.
                </div>
            @else
                <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <h1 class="text-xl font-black text-slate-900">Account Access Restricted</h1>
                <p class="text-sm text-slate-600 mt-2">
                    Your vendor profile <strong class="text-slate-900">{{ $vendor->display_name }}</strong> is currently {{ $vendor->status }}.
                </p>
                <div class="mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-900 leading-relaxed text-left">
                    {{ $message }}
                </div>
            @endif

            <div class="mt-6 flex flex-col gap-2">
                <form method="POST" action="{{ route('vendor.logout') }}">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Sign Out
                    </button>
                </form>
                <a href="{{ url('/') }}" class="text-xs text-slate-400 hover:text-slate-600">
                    &larr; Return to Storefront
                </a>
            </div>
        </div>
    </div>
</body>
</html>
