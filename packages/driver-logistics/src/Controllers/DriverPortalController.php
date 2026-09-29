<?php

namespace Packages\DriverLogistics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverDispatchService;
use Packages\DriverLogistics\Services\DriverExceptionService;
use Packages\DriverLogistics\Services\DriverPodService;

class DriverPortalController extends Controller
{
    protected function getDriver(Request $request): Driver
    {
        $driver = Driver::where('user_id', $request->user()->id)->first();

        if (! $driver && $request->user()->hasRole('admin')) {
            // Support admin previewing driver view
            $driver = Driver::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['license_number' => 'ADMIN-FLEET-01', 'vehicle_number' => 'KA-01-EXP-9999', 'status' => 'active']
            );
        }

        abort_if(! $driver, 403, 'Unauthorized. Fleet driver profile required.');

        return $driver;
    }

    public function dashboard(Request $request): View
    {
        $driver = $this->getDriver($request);

        $activeShipments = Shipment::where('driver_id', $driver->id)
            ->whereIn('status', ['pending', 'in_transit', 'out_for_delivery'])
            ->with(['order', 'warehouse'])
            ->latest('dispatched_at')
            ->get();

        $deliveredToday = Shipment::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->with('order')
            ->get();

        return view('driver-logistics::dashboard', [
            'driver' => $driver,
            'activeShipments' => $activeShipments,
            'deliveredToday' => $deliveredToday,
        ]);
    }

    public function showShipment(Request $request, int $id): View
    {
        $driver = $this->getDriver($request);

        $shipment = Shipment::where('driver_id', $driver->id)
            ->with(['order.items.variant.product', 'order.user', 'warehouse', 'exceptions'])
            ->findOrFail($id);

        return view('driver-logistics::shipment-detail', [
            'driver' => $driver,
            'shipment' => $shipment,
        ]);
    }

    public function startDelivery(Request $request, int $id, DriverDispatchService $dispatchService): RedirectResponse
    {
        $driver = $this->getDriver($request);
        $shipment = Shipment::where('driver_id', $driver->id)->findOrFail($id);

        $shipment->update([
            'status' => 'out_for_delivery',
            'dispatched_at' => now(),
        ]);

        $order = $shipment->order;
        if ($order && in_array($order->status, ['confirmed', 'picking', 'packed', 'pending'])) {
            $order->update(['status' => 'out_for_delivery']);
        }

        return back()->with('success', 'Trip started! Shipment marked as Out for Delivery.');
    }

    public function submitPod(Request $request, int $id, DriverPodService $podService): RedirectResponse
    {
        $driver = $this->getDriver($request);
        $shipment = Shipment::where('driver_id', $driver->id)->findOrFail($id);

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:150'],
            'otp' => ['nullable', 'string', 'max:10'],
            'signature_data' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo' => ['nullable', 'image', 'max:5120'], // 5MB max
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pod-photos', 'public');
        }

        try {
            $podService->submitProofOfDelivery($shipment, $driver, [
                'recipient_name' => $validated['recipient_name'],
                'otp' => $validated['otp'] ?? null,
                'signature_data' => $validated['signature_data'] ?? null,
                'photo_path' => $photoPath,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.dashboard')
            ->with('success', "Delivery completed for Shipment #{$shipment->shipment_number}! POD successfully recorded.");
    }

    public function reportException(Request $request, int $id, DriverExceptionService $exceptionService): RedirectResponse
    {
        $driver = $this->getDriver($request);
        $shipment = Shipment::where('driver_id', $driver->id)->findOrFail($id);

        $validated = $request->validate([
            'exception_code' => ['required', 'string', 'in:CUSTOMER_UNAVAILABLE,WRONG_ADDRESS,PINCODE_NOT_SERVICEABLE,STOCK_SHORTAGE,VEHICLE_ISSUE,SITE_BLOCKED'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        try {
            $exceptionService->recordException($shipment, $driver, $validated['exception_code'], $validated['notes']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.dashboard')
            ->with('warning', "Delivery exception logged for Shipment #{$shipment->shipment_number}. Central dispatch notified.");
    }

    public function toggleDutyStatus(Request $request): RedirectResponse
    {
        $driver = $this->getDriver($request);
        $newStatus = $driver->status === 'active' ? 'off_duty' : 'active';
        $driver->update(['status' => $newStatus]);

        return back()->with('success', 'Shift status updated to '.($newStatus === 'active' ? 'On Duty' : 'Off Duty').'.');
    }
}
