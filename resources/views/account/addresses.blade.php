@extends('account.layout')

@section('title', 'Saved Delivery Addresses & Sites')

@section('account_content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Delivery Addresses &amp; Project Sites</h1>
            <p class="text-xs text-slate-500">Manage construction site yards, depots, and recurring delivery destinations for seamless 1-click checkout.</p>
        </div>
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

    <!-- Saved Addresses Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($addresses as $addr)
            <div class="bg-white rounded-2xl border {{ $addr->is_default ? 'border-indigo-500 ring-2 ring-indigo-50 shadow-sm' : 'border-slate-200' }} p-5 space-y-3 flex flex-col justify-between transition-all">
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-xs text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $addr->label ?: 'Saved Address' }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            @if($addr->is_default)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Primary Default
                                </span>
                            @endif
                            @if($addr->is_site_address)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                    Project Site
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-xs text-slate-700 space-y-0.5">
                        <div class="font-bold text-slate-900">{{ $addr->recipient_name }}</div>
                        <div class="text-slate-500 font-mono">{{ $addr->phone }}</div>
                        <div class="pt-1 text-slate-600">{{ $addr->address_line_1 }}</div>
                        @if($addr->address_line_2)
                            <div class="text-slate-600">{{ $addr->address_line_2 }}</div>
                        @endif
                        @if($addr->landmark)
                            <div class="text-slate-500 text-[11px]">Landmark: {{ $addr->landmark }}</div>
                        @endif
                        <div class="font-semibold text-slate-900">{{ $addr->city }}, {{ $addr->state }} — <span class="font-mono">{{ $addr->pincode }}</span></div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div>
                        @if(! $addr->is_default)
                            <form action="{{ route('account.addresses.default', $addr->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                    Set as Primary Default
                                </button>
                            </form>
                        @endif
                    </div>
                    <form action="{{ route('account.addresses.destroy', $addr->id) }}" method="POST" onsubmit="return confirm('Remove this saved delivery destination?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold transition">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                No saved delivery addresses or construction site destinations yet. Add your first address below.
            </div>
        @endforelse
    </div>

    <!-- Add New Address Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Delivery Destination / Site Yard</span>
        </h2>

        <form action="{{ route('account.addresses.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Label (e.g. Site 4B, Central Yard)</label>
                    <input type="text" name="label" value="{{ old('label') }}" placeholder="e.g. Whitefield Site Office" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient / Site Contact Person</label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', auth()->user()?->name) }}" required placeholder="e.g. Ramesh Site Incharge" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone (Gate Verification)</label>
                    <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required placeholder="+91 9876543210" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address / Site Gate Details</label>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" required placeholder="e.g. Gate #2, Plot 45, EPIP Zone" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Address Line 2 (Optional)</label>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="e.g. Behind Metro Pillar 104" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Landmark (Optional)</label>
                    <input type="text" name="landmark" value="{{ old('landmark') }}" placeholder="e.g. Near Big Banyan Tree" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', 'Bengaluru') }}" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">State</label>
                    <input type="text" name="state" value="{{ old('state', 'Karnataka') }}" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Postal Pincode</label>
                    <input type="text" name="pincode" value="{{ old('pincode', '560066') }}" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                </div>
            </div>

            <div class="pt-2 flex flex-wrap items-center gap-6 text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                    <input type="checkbox" name="is_site_address" value="1" {{ old('is_site_address') ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span>Active Construction / Industrial Project Site (Heavy transport permits apply)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span>Set as primary default address</span>
                </label>
            </div>

            <div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Save Delivery Destination &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
