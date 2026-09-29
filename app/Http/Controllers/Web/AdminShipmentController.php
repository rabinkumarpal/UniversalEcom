<?php

namespace App\Http\Controllers\Web;

use App\Domain\Delivery\ShipmentService;
use App\Http\Controllers\Controller;
use App\Models\DeliveryException;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\DriverLogistics\Models\Driver;

class AdminShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    public function index(Request $request): View
    {
        $query = Shipment::with(['order.user', 'exceptions']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $shipments = $query->orderByDesc('created_at')->paginate(15);
        $totalShipments = Shipment::count();
        $inTransitCount = Shipment::whereIn('status', ['in_transit', 'out_for_delivery'])->count();
        $deliveredCount = Shipment::where('status', 'delivered')->count();
        $exceptionCount = DeliveryException::where('is_resolved', false)->count();

        return view('admin.shipments.index', compact(
            'shipments',
            'totalShipments',
            'inTransitCount',
            'deliveredCount',
            'exceptionCount'
        ));
    }

    public function show(int $id): View
    {
        $shipment = Shipment::with(['order.items', 'order.user', 'exceptions.recordedBy', 'warehouse', 'driver.user'])->findOrFail($id);
        $availableDrivers = class_exists(Driver::class)
            ? Driver::with('user')->where('status', 'active')->get()
            : collect();

        return view('admin.shipments.show', compact('shipment', 'availableDrivers'));
    }

    public function dispatch(Request $request, int $id): RedirectResponse
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'driver_id' => 'nullable|integer',
            'carrier_or_driver_name' => 'nullable|string|max:150',
            'driver_phone' => 'nullable|string|max:30',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! empty($validated['driver_id'])) {
            $driver = Driver::with('user')->findOrFail($validated['driver_id']);
            $driverName = $driver->user?->name ?? 'Fleet Driver';
            $vehicleDesc = $driver->vehicle_number ? " ({$driver->vehicle_number})" : '';
            $validated['carrier_or_driver_name'] = $driverName.$vehicleDesc;
            $validated['driver_phone'] = $driver->user?->phone ?? ($validated['driver_phone'] ?? null);
        }

        if (empty($validated['carrier_or_driver_name'])) {
            return back()->withErrors(['carrier_or_driver_name' => 'Please select a fleet driver or enter a carrier/driver name.']);
        }

        $this->shipmentService->dispatchShipment($shipment, $validated);

        return redirect()->route('admin.shipments.show', $shipment->id)
            ->with('success', 'Shipment successfully dispatched with driver assigned.');
    }

    public function recordPod(Request $request, int $id): RedirectResponse
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'pod_recipient_name' => 'required|string|max:150',
            'pod_otp' => 'nullable|string|max:20',
            'pod_signature' => 'nullable|string',
            'pod_latitude' => 'nullable|numeric',
            'pod_longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->shipmentService->recordProofOfDelivery($shipment, $validated);

        return redirect()->route('admin.shipments.show', $shipment->id)
            ->with('success', 'Proof of delivery successfully verified and recorded.');
    }

    public function recordException(Request $request, int $id): RedirectResponse
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'exception_code' => 'required|string',
            'notes' => 'required|string|max:500',
        ]);

        $this->shipmentService->recordException(
            $shipment,
            $validated['exception_code'],
            $validated['notes'],
            $request->user()?->id
        );

        return redirect()->route('admin.shipments.show', $shipment->id)
            ->with('warning', 'Delivery exception recorded. Shipment status set to failed.');
    }
}
