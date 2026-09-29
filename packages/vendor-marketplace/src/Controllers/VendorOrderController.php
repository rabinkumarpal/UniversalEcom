<?php

namespace Packages\VendorMarketplace\Controllers;

use App\Models\OrderStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOrder;

class VendorOrderController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $query = VendorOrder::where('vendor_id', $vendor->id)
            ->with(['order', 'items']);

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        $statusCounts = VendorOrder::where('vendor_id', $vendor->id)
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        $totalOrdersCount = VendorOrder::where('vendor_id', $vendor->id)->count();

        return view('vendor-marketplace::orders.index', [
            'vendor' => $vendor,
            'orders' => $orders,
            'currentStatus' => $request->query('status', 'all'),
            'statusCounts' => $statusCounts,
            'totalOrdersCount' => $totalOrdersCount,
        ]);
    }

    public function show(Request $request, int $id): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $vendorOrder = VendorOrder::where('vendor_id', $vendor->id)
            ->with(['order.user', 'order.statusHistory.user', 'items.orderItem.variant.product'])
            ->findOrFail($id);

        return view('vendor-marketplace::orders.show', [
            'vendor' => $vendor,
            'vendorOrder' => $vendorOrder,
        ]);
    }

    public function packingSlip(Request $request, int $id): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $vendorOrder = VendorOrder::where('vendor_id', $vendor->id)
            ->with(['order.user', 'items.orderItem.variant.product'])
            ->findOrFail($id);

        return view('vendor-marketplace::orders.packing-slip', [
            'vendor' => $vendor,
            'vendorOrder' => $vendorOrder,
        ]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $vendorOrder = VendorOrder::where('vendor_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,picking,packed,dispatched,delivered,cancelled'],
            'tracking_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $vendorOrder->status;

        $vendorOrder->update([
            'status' => $validated['status'],
        ]);

        // Record in OrderStatusHistory on the parent platform order
        if ($vendorOrder->order_id) {
            $vendorName = $vendor->display_name ?: $vendor->legal_name;
            $notes = "[Vendor: {$vendorName}] Fulfillment updated to ".ucfirst($validated['status']);
            if (! empty($validated['tracking_notes'])) {
                $notes .= ' — Note: '.$validated['tracking_notes'];
            }

            OrderStatusHistory::create([
                'order_id' => $vendorOrder->order_id,
                'previous_status' => $previousStatus,
                'new_status' => $validated['status'],
                'note' => $notes,
                'user_id' => auth()->id(),
            ]);
        }

        return back()->with('success', "Order #{$vendorOrder->vendor_order_number} status updated to ".ucfirst($validated['status']).'.');
    }
}
