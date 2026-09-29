@extends('account.layout')

@section('title', 'Profile & Account Credentials')

@section('account_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Profile &amp; Account Settings</h1>
        <p class="text-xs text-slate-500">Manage your contractor profile information, primary contact numbers, and security credentials.</p>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">Please correct the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Personal Information -->
            <div class="space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Personal &amp; Contact Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name / Primary Contact</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Phone (For OTP &amp; Delivery Tracking)</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+91 9876543210" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Registered Email Address</label>
                        <input type="email" value="{{ $user->email }}" disabled class="w-full px-3 py-2 text-xs border border-slate-200 bg-slate-50 text-slate-500 rounded-xl cursor-not-allowed">
                        <p class="text-[11px] text-slate-400 mt-1">To change your primary registered login email, contact platform security administration.</p>
                    </div>
                </div>
            </div>

            <!-- Password Change (Optional) -->
            <div class="space-y-4 pt-2">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Change Password (Leave blank to keep current)</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                        <input type="password" name="password" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">Account Created: {{ $user->created_at->format('M d, Y') }}</span>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Save Changes &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
