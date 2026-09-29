<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $addresses,
            'meta' => ['total' => $addresses->count()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:150',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'nullable|string|max:2',
            'pincode' => 'required|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
            'is_site_address' => 'nullable|boolean',
        ]);

        $user = $request->user();

        if (! empty($validated['is_default'])) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id' => $user->id,
            'label' => $validated['label'] ?? 'Home',
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'] ?? 'IN',
            'pincode' => $validated['pincode'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'is_site_address' => $validated['is_site_address'] ?? false,
        ]);

        return response()->json([
            'data' => $address,
            'meta' => ['message' => 'Delivery address saved successfully.'],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'recipient_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'pincode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
            'is_site_address' => 'nullable|boolean',
        ]);

        if (! empty($validated['is_default'])) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update(array_filter($validated, fn ($val) => $val !== null));

        return response()->json([
            'data' => $address->fresh(),
            'meta' => ['message' => 'Delivery address updated successfully.'],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($id);
        $address->delete();

        return response()->json([
            'meta' => ['message' => 'Delivery address deleted successfully.'],
        ]);
    }
}
