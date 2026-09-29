@extends('driver-logistics::layouts.driver')

@section('title', 'Shipment ' . $shipment->shipment_number)

@section('content')
@php
    $address = $shipment->order?->shipping_address_snapshot ?? [];
    $fullAddress = trim(($address['address_line_1'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['pincode'] ?? ''));
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($fullAddress);
    $phone = $address['phone'] ?? null;
@endphp

<div class="space-y-4">
    <!-- Back Link -->
    <div>
        <a href="{{ route('driver.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-400 hover:text-amber-300 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Route Manifest</span>
        </a>
    </div>

    <!-- Drop Details Card -->
    <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-lg space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Consignment Drop</span>
                <h2 class="text-base font-black text-white font-mono">{{ $shipment->shipment_number }}</h2>
                <div class="text-xs text-slate-400 font-mono">Order #{{ $shipment->order?->order_number }}</div>
            </div>

            @php
                $statusColors = [
                    'pending' => 'bg-slate-700 text-slate-300 border-slate-600',
                    'in_transit' => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                    'out_for_delivery' => 'bg-amber-500/20 text-amber-300 border-amber-500/40 animate-pulse',
                    'delivered' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                    'failed' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                ];
            @endphp
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusColors[$shipment->status] ?? 'bg-slate-700 text-slate-300' }}">
                {{ str_replace('_', ' ', $shipment->status) }}
            </span>
        </div>

        <div class="pt-2 border-t border-slate-700/60 space-y-2 text-xs">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-bold text-white text-sm">{{ $address['recipient_name'] ?? 'Recipient' }}</div>
                    <div class="text-slate-300 mt-0.5">{{ $fullAddress }}</div>
                </div>

                @if($phone)
                    <a href="tel:{{ $phone }}" class="p-2.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/30 transition shrink-0" title="Call Customer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </a>
                @endif
            </div>

            <a href="{{ $mapsUrl }}" target="_blank" class="w-full py-2 px-3 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Navigate to Site in Google Maps</span>
            </a>
        </div>
    </div>

    <!-- Materials Pick-List Manifest -->
    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/80 shadow-md space-y-2.5">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Cargo Items to Unload</h3>
            <span class="text-[10px] text-slate-500 font-mono">{{ $shipment->order?->items?->count() ?? 0 }} line items</span>
        </div>

        <div class="divide-y divide-slate-700/60 text-xs">
            @forelse($shipment->order?->items ?? [] as $item)
                <div class="py-2.5 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-bold text-white">{{ $item->product_name_snapshot }}</div>
                        <div class="text-[11px] text-slate-400">
                            {{ $item->variant_name_snapshot }} • SKU: <span class="font-mono text-slate-300">{{ $item->sku_snapshot }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="px-2 py-1 rounded-lg bg-slate-700 font-black font-mono text-amber-400 text-xs">
                            {{ $item->quantity }} Units
                        </span>
                    </div>
                </div>
            @empty
                <div class="py-2 text-slate-500 text-xs">No items listed.</div>
            @endforelse
        </div>
    </div>

    <!-- Trip Action: If not yet out for delivery -->
    @if($shipment->status !== 'out_for_delivery' && $shipment->status !== 'delivered' && $shipment->status !== 'failed')
        <form method="POST" action="{{ route('driver.shipments.start', $shipment->id) }}">
            @csrf
            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm tracking-wide shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Start Trip (Mark Out for Delivery)</span>
            </button>
        </form>
    @endif

    <!-- Completed Delivery POD Details Display -->
    @if($shipment->status === 'delivered')
        <div class="p-5 rounded-2xl bg-emerald-950/60 border border-emerald-800/80 shadow-lg space-y-3">
            <div class="flex items-center gap-2 text-emerald-400 font-black text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Delivery Completed &amp; Verified</span>
            </div>

            <div class="space-y-1.5 text-xs text-slate-300">
                <div>Recipient: <strong class="text-white">{{ $shipment->pod_recipient_name }}</strong></div>
                @if($shipment->pod_otp)
                    <div>Verified Delivery OTP: <span class="font-mono font-bold text-amber-400">{{ $shipment->pod_otp }}</span></div>
                @endif
                <div>Delivered At: <span class="font-mono text-slate-300">{{ $shipment->delivered_at?->format('M d, Y H:i:s') }}</span></div>
                @if($shipment->pod_latitude && $shipment->pod_longitude)
                    <div>GPS Site Geo: <span class="font-mono text-slate-300">{{ $shipment->pod_latitude }}, {{ $shipment->pod_longitude }}</span></div>
                @endif
            </div>

            @if($shipment->pod_signature_data)
                <div class="pt-2 border-t border-emerald-900/60">
                    <div class="text-[11px] font-bold text-slate-400 mb-1">Customer Digital Signature:</div>
                    <div class="p-2 rounded-xl bg-slate-900 border border-emerald-900/60 inline-block">
                        <img src="{{ $shipment->pod_signature_data }}" alt="Customer Signature" class="max-h-24 object-contain">
                    </div>
                </div>
            @endif

            @if($shipment->pod_photo_path)
                <div class="pt-2 border-t border-emerald-900/60">
                    <div class="text-[11px] font-bold text-slate-400 mb-1">Site Unloading Photo:</div>
                    <img src="{{ asset('storage/' . $shipment->pod_photo_path) }}" alt="Unloading Photo" class="w-full rounded-xl border border-slate-700 max-h-48 object-cover">
                </div>
            @endif
        </div>
    @endif

    <!-- POD Submission Form: When out for delivery -->
    @if($shipment->status === 'out_for_delivery')
        <div class="p-5 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-xl space-y-4">
            <div>
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span>Digital Proof of Delivery (POD)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Collect OTP, signature, site photo, and GPS to confirm handover.</p>
            </div>

            <form method="POST" action="{{ route('driver.shipments.pod', $shipment->id) }}" enctype="multipart/form-data" id="podForm" class="space-y-4">
                @csrf

                <!-- Recipient Name -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Receiver Full Name <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="text"
                        name="recipient_name"
                        required
                        value="{{ old('recipient_name', $address['recipient_name'] ?? '') }}"
                        placeholder="e.g. Suresh Gowda (Site Manager)"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                    >
                </div>

                <!-- 6-Digit Delivery OTP -->
                <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-black uppercase tracking-wider text-amber-300">
                            6-Digit Delivery OTP @if(!empty($shipment->delivery_otp))<span class="text-rose-400">*</span>@else<span class="text-[10px] text-slate-400 lowercase font-normal">(optional)</span>@endif
                        </label>
                        <span class="text-[10px] text-amber-400/80 font-mono">Sent to customer</span>
                    </div>
                    <input
                        type="text"
                        name="otp"
                        maxlength="6"
                        @if(!empty($shipment->delivery_otp)) required pattern="[0-9]{6}" @endif
                        value="{{ old('otp') }}"
                        placeholder="{{ !empty($shipment->delivery_otp) ? 'Enter 6-digit OTP' : 'OTP not generated for this drop' }}"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-amber-500/40 text-amber-400 font-mono font-black text-center text-lg tracking-widest placeholder-slate-600 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 focus:outline-none"
                    >
                    <p class="text-[10px] text-slate-400">Request the 6-digit security code provided to the recipient via order confirmation or SMS.</p>
                </div>

                <!-- Touch / Stylus HTML5 Signature Canvas -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            Customer Digital Signature
                        </label>
                        <button type="button" id="clearCanvasBtn" class="text-[10px] text-amber-400 hover:text-amber-300 font-bold">
                            Clear Signature
                        </button>
                    </div>
                    <div class="relative bg-slate-950 rounded-xl border border-slate-700 p-1">
                        <canvas id="signatureCanvas" class="w-full h-32 rounded-lg touch-none bg-slate-950 cursor-crosshair"></canvas>
                        <div id="signatureHint" class="absolute inset-0 flex items-center justify-center text-xs text-slate-600 pointer-events-none">
                            Sign with finger or stylus here
                        </div>
                    </div>
                    <input type="hidden" name="signature_data" id="signatureData">
                </div>

                <!-- Site Unloading Photo Upload -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Site Unloading Photo (Optional)
                    </label>
                    <input
                        type="file"
                        name="photo"
                        id="photoInput"
                        accept="image/*"
                        capture="environment"
                        class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-slate-200 hover:file:bg-slate-600"
                    >
                </div>

                <!-- GPS Geolocation Capture -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            GPS Coordinates
                        </label>
                        <button type="button" id="getGpsBtn" class="text-[10px] text-blue-400 hover:text-blue-300 font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            <span>Capture Site GPS</span>
                        </button>
                    </div>
                    <div id="gpsStatus" class="p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-[11px] font-mono text-slate-400">
                        GPS coordinate not yet acquired
                    </div>
                    <input type="hidden" name="latitude" id="latitudeInput">
                    <input type="hidden" name="longitude" id="longitudeInput">
                </div>

                <!-- Handover Notes -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Handover Notes
                    </label>
                    <input
                        type="text"
                        name="notes"
                        placeholder="e.g. Unloaded 50 bags cement at North entrance"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                    >
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submitPodBtn"
                    class="w-full py-3.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm tracking-wide shadow-xl transition flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Verify OTP &amp; Confirm Delivery</span>
                </button>
            </form>
        </div>

        <!-- Exception Accordion / Trigger -->
        <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-900/60 shadow-lg space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Unable to Deliver? Report Issue</span>
                </div>
                <button type="button" onclick="document.getElementById('exceptionForm').classList.toggle('hidden')" class="text-[11px] font-bold text-rose-300 hover:underline">
                    Toggle Issue Form
                </button>
            </div>

            <form id="exceptionForm" method="POST" action="{{ route('driver.shipments.exception', $shipment->id) }}" class="hidden space-y-3 pt-2 border-t border-rose-900/50">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Exception Reason
                    </label>
                    <select name="exception_code" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-rose-800 text-white text-xs focus:border-rose-500 focus:outline-none">
                        <option value="CUSTOMER_UNAVAILABLE">Customer / Site In-charge Unavailable</option>
                        <option value="WRONG_ADDRESS">Wrong Address / Site Gate Inaccessible</option>
                        <option value="SITE_BLOCKED">Site Blocked / No Access for Heavy Vehicle</option>
                        <option value="VEHICLE_ISSUE">Vehicle Breakdown / Tire Puncture</option>
                        <option value="STOCK_SHORTAGE">Material Damage / Client Refusal</option>
                        <option value="PINCODE_NOT_SERVICEABLE">Road Restriction / Commercial Truck Ban</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Incident Details
                    </label>
                    <textarea name="notes" rows="2" required placeholder="Describe what happened at the delivery location..."
                              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-rose-800 text-white text-xs placeholder-slate-500 focus:border-rose-500 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs transition shadow-md">
                    Log Exception &amp; Abort Delivery &rarr;
                </button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Signature Canvas Logic
    const canvas = document.getElementById('signatureCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const signatureDataInput = document.getElementById('signatureData');
        const hint = document.getElementById('signatureHint');
        let isDrawing = false;
        let hasDrawn = false;

        // Resize canvas to element client rect
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * (window.devicePixelRatio || 1);
            canvas.height = rect.height * (window.devicePixelRatio || 1);
            ctx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#38bdf8'; // Sky blue ink
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            if (e.touches && e.touches[0]) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }

        function start(e) {
            e.preventDefault();
            isDrawing = true;
            hasDrawn = true;
            if (hint) hint.style.display = 'none';
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stop(e) {
            if (!isDrawing) return;
            isDrawing = false;
            if (hasDrawn) {
                signatureDataInput.value = canvas.toDataURL('image/png');
            }
        }

        // Mouse events
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stop);

        // Touch events
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stop);

        // Clear canvas
        const clearBtn = document.getElementById('clearCanvasBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                signatureDataInput.value = '';
                hasDrawn = false;
                if (hint) hint.style.display = 'flex';
            });
        }
    }

    // 2. Geolocation Capture
    const getGpsBtn = document.getElementById('getGpsBtn');
    const gpsStatus = document.getElementById('gpsStatus');
    const latInput = document.getElementById('latitudeInput');
    const lngInput = document.getElementById('longitudeInput');

    function acquireGps() {
        if (!navigator.geolocation) {
            if (gpsStatus) gpsStatus.textContent = 'Geolocation not supported by device';
            return;
        }
        if (gpsStatus) gpsStatus.textContent = 'Acquiring GPS location...';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude.toFixed(6);
                const lng = pos.coords.longitude.toFixed(6);
                if (latInput) latInput.value = lat;
                if (lngInput) lngInput.value = lng;
                if (gpsStatus) {
                    gpsStatus.textContent = `Locked GPS: ${lat}, ${lng} (±${Math.round(pos.coords.accuracy)}m)`;
                    gpsStatus.classList.add('text-emerald-400', 'border-emerald-700');
                    gpsStatus.classList.remove('text-slate-400', 'border-slate-700');
                }
            },
            (err) => {
                if (gpsStatus) gpsStatus.textContent = `GPS unavailable (${err.message})`;
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }

    if (getGpsBtn) {
        getGpsBtn.addEventListener('click', acquireGps);
        // Auto attempt acquiring GPS on load if out for delivery
        acquireGps();
    }
});
</script>
@endpush
