@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Product Offers')
@section('subtitle', 'Manage your selling prices, SKUs and active status for catalog items')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h3 class="font-bold text-slate-900 text-base">Your Active Catalog Offers</h3>
            <p class="text-xs text-slate-500">Offers are matched against platform catalog products during customer discovery and checkout.</p>
        </div>
        <a href="{{ route('vendor.offers.create') }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Offer
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Product & Variant</th>
                        <th class="p-4">Vendor SKU</th>
                        <th class="p-4">Selling Price</th>
                        <th class="p-4">MRP</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Quick Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($offers as $offer)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <p class="font-bold text-slate-900">{{ $offer->variant->product->name ?? 'Catalog Item' }}</p>
                                <p class="text-slate-500 text-[11px]">{{ $offer->variant->title ?? 'Default Variant' }} (SKU: {{ $offer->variant->sku ?? 'N/A' }})</p>
                            </td>
                            <td class="p-4 font-mono text-slate-600">{{ $offer->vendor_sku ?? '-' }}</td>
                            <td class="p-4 font-bold text-slate-900 font-mono">₹{{ number_format($offer->vendor_price / 100, 2) }}</td>
                            <td class="p-4 text-slate-500 font-mono">
                                {{ $offer->vendor_mrp ? '₹' . number_format($offer->vendor_mrp / 100, 2) : '-' }}
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                    {{ $offer->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($offer->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <form method="POST" action="{{ route('vendor.offers.update', $offer->id) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <div class="relative w-24">
                                        <span class="absolute inset-y-0 left-2 flex items-center text-slate-400">₹</span>
                                        <input type="number" step="0.01" name="vendor_price" value="{{ $offer->vendor_price / 100 }}" required
                                               class="w-full pl-5 pr-2 py-1 border border-slate-300 rounded text-xs">
                                    </div>
                                    <input type="hidden" name="vendor_mrp" value="{{ $offer->vendor_mrp ? $offer->vendor_mrp / 100 : '' }}">
                                    <select name="status" class="border border-slate-300 rounded text-xs py-1 px-2">
                                        <option value="approved" {{ $offer->status === 'approved' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $offer->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <button type="submit" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded font-bold text-[11px] transition-colors">
                                        Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                You haven't added any product offers yet. Click "Add New Offer" to list products from the platform catalog.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($offers->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $offers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
