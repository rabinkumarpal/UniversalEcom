@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Vendor Profile & Settings')
@section('subtitle', 'Manage company registration, contact information, and store credentials')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="font-bold text-slate-900 text-base mb-1">Company Profile</h3>
        <p class="text-xs text-slate-500 mb-6">Information displayed on platform invoices and vendor storefront credentials</p>

        <form method="POST" action="{{ route('vendor.profile.update') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Public Display Name</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $vendor->display_name) }}" required
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Company Legal Name</label>
                    <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}" required
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" required
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Email (Login)</label>
                    <input type="email" value="{{ $vendor->email }}" disabled
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-500 text-sm cursor-not-allowed">
                    <p class="text-[11px] text-slate-400 mt-1">Email changes require admin support.</p>
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Vendor Slug</span>
                    <strong class="font-mono text-slate-800">{{ $vendor->slug }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Commission Fee</span>
                    <strong class="text-amber-700">{{ $vendor->commission_rate_percentage }}%</strong>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Account Status</span>
                    <strong class="text-emerald-700 capitalize">{{ $vendor->status }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Approved Date</span>
                    <strong class="text-slate-800">{{ $vendor->approved_at ? $vendor->approved_at->format('M d, Y') : 'Pre-approved' }}</strong>
                </div>
            </div>

            <div class="pt-3 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
