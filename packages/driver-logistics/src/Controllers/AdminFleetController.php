<?php

namespace Packages\DriverLogistics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverDispatchService;

class AdminFleetController extends Controller
{
    public function index(Request $request): View
    {
        $query = Driver::with(['user'])->withCount([
            'shipments',
            'shipments as active_shipments_count' => function ($q) {
                $q->whereIn('status', ['pending', 'in_transit', 'out_for_delivery']);
            },
            'shipments as delivered_shipments_count' => function ($q) {
                $q->where('status', 'delivered');
            },
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $drivers = $query->latest('id')->paginate(15);

        $eligibleUsers = User::whereDoesntHave('driver')
            ->orderBy('name')
            ->take(100)
            ->get();

        $totalDrivers = Driver::count();
        $activeDrivers = Driver::where('status', 'active')->count();
        $offDutyDrivers = Driver::where('status', 'off_duty')->count();
        $suspendedDrivers = Driver::where('status', 'suspended')->count();

        return view('admin.logistics.drivers', compact(
            'drivers',
            'eligibleUsers',
            'totalDrivers',
            'activeDrivers',
            'offDutyDrivers',
            'suspendedDrivers'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:drivers,user_id'],
            'license_number' => ['required', 'string', 'max:50'],
            'vehicle_type' => ['required', 'string', 'max:50'],
            'vehicle_number' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:active,off_duty,suspended'],
        ]);

        Driver::create($validated);

        return redirect()->route('admin.logistics.drivers.index')
            ->with('success', 'Driver profile successfully created and vehicle assigned.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:active,off_duty,suspended'],
        ]);

        $driver->update(['status' => $validated['status']]);

        return redirect()->route('admin.logistics.drivers.index')
            ->with('success', "Driver status updated to {$driver->status}.");
    }

    public function assignShipment(Request $request, int $id, DriverDispatchService $dispatchService): RedirectResponse
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'shipment_id' => ['required', 'exists:shipments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $dispatchService->assignDriverToShipment($shipment, $driver, [
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Shipment #{$shipment->shipment_number} dispatched to driver {$driver->user->name}. Delivery OTP generated: {$shipment->delivery_otp}");
    }
}
