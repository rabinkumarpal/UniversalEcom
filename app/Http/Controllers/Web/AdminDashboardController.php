<?php

namespace App\Http\Controllers\Web;

use App\Core\Registry\AddonRegistry;
use App\Domain\Inventory\InventoryService;
use App\Domain\Orders\OrderStateMachine;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\InvoiceGst\Services\GstInvoiceService;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected InventoryService $inventoryService,
        protected AddonRegistry $addonRegistry
    ) {}

    public function dashboard(): View
    {
        $totalRevenue = Order::whereIn('status', ['confirmed', 'paid', 'delivered'])->sum('grand_total');
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', ['confirmed', 'picking'])->count();
        $lowStockCount = InventoryItem::whereColumn('available', '<=', 'reorder_level')->count();

        $recentOrders = Order::with(['user', 'items'])->orderByDesc('created_at')->take(6)->get();
        $addons = $this->addonRegistry->all();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'pendingOrders',
            'lowStockCount',
            'recentOrders',
            'addons'
        ));
    }

    public function orders(Request $request): View
    {
        $query = Order::with(['user', 'items', 'payments', 'invoice', 'shipments']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('shipping_address_snapshot->recipient_name', 'like', "%{$search}%")
                    ->orWhere('shipping_address_snapshot->phone', 'like', "%{$search}%")
                    ->orWhere('shipping_address_snapshot->city', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $statusCounts = Order::groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status')->toArray();
        $totalOrdersCount = Order::count();

        return view('admin.orders', compact('orders', 'statusCounts', 'totalOrdersCount'));
    }

    public function showOrder(string $orderNumber): View
    {
        $order = Order::with([
            'user',
            'items.variant.product',
            'payments',
            'invoice',
            'gstInvoice',
            'shipments.driver.user',
            'shipments.warehouse',
            'statusHistory.user',
        ])->where('order_number', $orderNumber)->firstOrFail();

        return view('admin.orders.show', compact('order'));
    }

    public function packingSlip(string $orderNumber): View
    {
        $order = Order::with([
            'user',
            'items.variant.product',
            'items.variant.inventoryItems.warehouse',
            'shipments.driver.user',
            'shipments.warehouse',
            'statusHistory.user',
        ])->where('order_number', $orderNumber)->firstOrFail();

        return view('admin.orders.packing_slip', compact('order'));
    }

    public function generateInvoice(string $orderNumber, GstInvoiceService $invoiceService): RedirectResponse
    {
        $order = Order::with(['items.variant.product', 'user'])->where('order_number', $orderNumber)->firstOrFail();

        $invoice = $invoiceService->generateForOrder($order);

        return back()->with('success', "GST Tax Invoice #{$invoice->invoice_number} successfully issued.");
    }

    public function transitionOrder(Request $request, string $orderNumber): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->stateMachine->transitionTo($order, $validated['status'], auth()->id(), $validated['note'] ?? null);

        return back()->with('success', "Order [{$orderNumber}] status transitioned to {$validated['status']}.");
    }

    public function inventory(Request $request): View
    {
        $query = InventoryItem::with([
            'variant.product',
            'warehouse',
            'movements' => fn ($mq) => $mq->latest()->limit(5)->with('user'),
        ]);

        // Search by SKU, variant name, or product name
        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->whereHas('variant', function ($vq) use ($search) {
                    $vq->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        // Warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->query('warehouse_id'));
        }

        // Status filter
        $currentStatus = $request->query('status', 'all');
        if ($currentStatus === 'low_stock') {
            $query->whereColumn('available', '<=', 'reorder_level')->where('available', '>', 0);
        } elseif ($currentStatus === 'out_of_stock') {
            $query->where('available', '<=', 0);
        } elseif ($currentStatus === 'healthy') {
            $query->whereColumn('available', '>', 'reorder_level');
        }

        $inventory = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Summary KPI Metrics
        $totalSkus = InventoryItem::count();
        $lowStockCount = InventoryItem::whereColumn('available', '<=', 'reorder_level')->where('available', '>', 0)->count();
        $outOfStockCount = InventoryItem::where('available', '<=', 0)->count();
        $totalOnHand = (int) InventoryItem::sum('on_hand');
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory', compact(
            'inventory',
            'totalSkus',
            'lowStockCount',
            'outOfStockCount',
            'totalOnHand',
            'warehouses',
            'currentStatus'
        ));
    }

    public function adjustStock(Request $request, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity_change' => 'nullable|integer',
            'reorder_level' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($itemId);

        if (! empty($validated['quantity_change'])) {
            $this->inventoryService->adjustStock(
                $item,
                (int) $validated['quantity_change'],
                'adjustment',
                $validated['reason'],
                auth()->id()
            );
        }

        if (isset($validated['reorder_level']) && $validated['reorder_level'] !== '') {
            $item->update(['reorder_level' => (int) $validated['reorder_level']]);
        }

        return back()->with('success', "Stock updated for SKU [{$item->variant?->sku}].");
    }

    public function transferStock(Request $request, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'destination_warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::with(['warehouse', 'variant'])->findOrFail($itemId);
        $destWarehouse = Warehouse::findOrFail($validated['destination_warehouse_id']);

        try {
            $this->inventoryService->transferStock(
                $item,
                $destWarehouse,
                (int) $validated['quantity'],
                $validated['reason'] ?? null,
                auth()->id()
            );

            return back()->with('success', "Transferred {$validated['quantity']} unit(s) of SKU [{$item->variant?->sku}] from {$item->warehouse?->name} to {$destWarehouse->name}.");
        } catch (\RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function addons(): View
    {
        $addons = $this->addonRegistry->all();

        return view('admin.addons', compact('addons'));
    }
}
