@extends('layouts.admin')

@section('title', 'WhatsApp Communications')
@section('header_title', 'WhatsApp & SMS Communications Hub')
@section('header_subtitle', 'Automated customer messaging, live dispatch alerts, delivery OTPs & webhook delivery receipts')

@section('content')
<div class="space-y-6">
    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Dispatched</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($totalLogs) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">WhatsApp & SMS Ledger</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400">Delivered</span>
            <div class="text-2xl font-black text-sky-600 dark:text-sky-400 font-mono mt-1">{{ number_format($deliveredCount) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ $deliveryRate }}% Delivery Rate</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Read Receipts</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ number_format($readCount) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ $readRate }}% Read Rate</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Delivery Failures</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">{{ number_format($failedCount) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Unreachable or Bounced</div>
        </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('admin.communications.whatsapp.index') }}" class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search phone, recipient, message ID..."
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs w-64 focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                >

                <select
                    name="channel"
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                >
                    <option value="all">All Channels</option>
                    <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="sms" {{ request('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                </select>

                <select
                    name="status"
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                >
                    <option value="all">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white dark:bg-slate-200 dark:text-slate-900 text-xs font-bold hover:opacity-90">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'channel', 'status']))
                    <a href="{{ route('admin.communications.whatsapp.index') }}" class="px-2 py-1.5 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                        Reset
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.communications.whatsapp.templates') }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-900">
                    Templates Manager
                </a>
                <button onclick="document.getElementById('test-dispatch-modal').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">
                    + Dispatch Test Message
                </button>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">Time</th>
                        <th class="p-3">Recipient</th>
                        <th class="p-3">Channel</th>
                        <th class="p-3">Template</th>
                        <th class="p-3">Message Content</th>
                        <th class="p-3">Order / Reference</th>
                        <th class="p-3">Gateway ID</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                            <td class="p-3 text-slate-500 whitespace-nowrap">
                                <div>{{ $log->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $log->recipient_name ?? 'Customer' }}</div>
                                <div class="text-slate-500 font-mono text-[11px]">{{ $log->recipient_phone }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($log->channel === 'whatsapp')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                        WhatsApp
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/80 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                                        SMS
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                                {{ $log->template_name ?: 'Custom Dispatch' }}
                            </td>
                            <td class="p-3 max-w-xs truncate text-slate-600 dark:text-slate-400 font-mono text-[11px]" title="{{ $log->rendered_message }}">
                                {{ Str::limit($log->rendered_message, 70) }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($log->order)
                                    <a href="{{ route('admin.orders.show', $log->order->id) }}" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                        {{ $log->order->order_number }}
                                    </a>
                                @elseif($log->shipment_id)
                                    <span class="text-slate-600 dark:text-slate-400 font-mono">Shipment #{{ $log->shipment_id }}</span>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap font-mono text-[11px] text-slate-500">
                                {{ $log->gateway_message_id ?? 'N/A' }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($log->status === 'read')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1 w-fit">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Read
                                    </span>
                                @elseif($log->status === 'delivered')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/80 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                                        Delivered
                                    </span>
                                @elseif($log->status === 'sent')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                        Sent
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800" title="{{ $log->error_message }}">
                                        Failed
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                No customer notification logs found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Test Dispatch Modal -->
<div id="test-dispatch-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Simulate Outbound Notification</h3>
            <button onclick="document.getElementById('test-dispatch-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.communications.whatsapp.test') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Recipient Mobile Number</label>
                <input type="text" name="recipient_phone" required placeholder="e.g. 9876543210" class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500 font-mono">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Recipient Name</label>
                <input type="text" name="recipient_name" placeholder="e.g. Ramesh Kumar" class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Communication Template</label>
                <select name="template_key" required class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="order_confirmed">Order Confirmation</option>
                    <option value="shipment_dispatched">Consignment Out for Delivery (with OTP)</option>
                    <option value="delivery_otp">Delivery OTP Verification PIN</option>
                    <option value="pod_completed">Proof of Delivery Completed (POD)</option>
                    <option value="delivery_exception">Delivery Exception Alert</option>
                </select>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('test-dispatch-modal').classList.add('hidden')" class="px-3 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg shadow-xs">
                    Dispatch Notification
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
