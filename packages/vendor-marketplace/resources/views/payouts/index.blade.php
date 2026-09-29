@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Payouts & Settlement Ledger')
@section('subtitle', 'Transparent ledger tracking gross sales, platform commissions, and bank disbursements')

@section('content')
<div class="space-y-6">
    <!-- Payout Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Earned Payout</p>
            <p class="text-2xl font-black text-slate-900 mt-1 font-mono">₹{{ number_format($totalEarned / 100, 2) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Accumulated from all fulfilled orders</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Settled & In Process</p>
            <p class="text-2xl font-black text-indigo-600 mt-1 font-mono">₹{{ number_format($totalCommitted / 100, 2) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Paid out or pending admin transfer</p>
        </div>

        <div class="bg-gradient-to-br from-emerald-900 to-slate-900 text-white p-5 rounded-2xl shadow-md flex flex-col justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-300 uppercase tracking-wider">Unsettled Balance</p>
                <p class="text-2xl font-black text-white mt-1 font-mono">₹{{ number_format($unsettledBalance / 100, 2) }}</p>
                <p class="text-[11px] text-slate-300 mt-1">Available for disbursement</p>
            </div>

            <div class="mt-4 pt-3 border-t border-white/10">
                @if($unsettledBalance >= 100)
                    <form method="POST" action="{{ route('vendor.payouts.request') }}">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition-colors">
                            Request Payout Disbursement
                        </button>
                    </form>
                @else
                    <button disabled class="w-full py-2 px-3 bg-white/10 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed">
                        No Unsettled Balance
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-sm">Disbursement History</h3>
            <p class="text-xs text-slate-500">Historical statements and bank transfers processed by platform administration</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Payout Statement #</th>
                        <th class="p-4">Disbursement Amount</th>
                        <th class="p-4">Requested Date</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Settled At</th>
                        <th class="p-4">Bank / UPI Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($payouts as $payout)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-900">{{ $payout->payout_number }}</td>
                            <td class="p-4 font-mono font-bold text-emerald-600 text-sm">₹{{ number_format($payout->amount / 100, 2) }}</td>
                            <td class="p-4 text-slate-600">{{ $payout->created_at->format('M d, Y') }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold
                                    {{ $payout->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $payout->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $payout->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ $payout->paid_at ? $payout->paid_at->format('M d, Y h:i A') : 'Pending settlement' }}
                            </td>
                            <td class="p-4 font-mono text-slate-700">
                                {{ $payout->payment_reference ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                No payout requests yet. As your split orders are delivered and balance accumulates, you can request settlements here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
