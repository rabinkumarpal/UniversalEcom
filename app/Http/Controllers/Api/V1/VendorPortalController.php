<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorPayout;
use Packages\VendorMarketplace\Models\VendorUser;

class VendorPortalController extends Controller
{
    /**
     * Resolve vendor for the authenticated user.
     */
    protected function resolveVendor(Request $request)
    {
        $vendorUser = VendorUser::with('vendor')
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->first();

        if (! $vendorUser || ! $vendorUser->vendor) {
            abort(403, 'User is not associated with any active vendor account.');
        }

        return $vendorUser->vendor;
    }

    public function profile(Request $request): JsonResponse
    {
        $vendor = $this->resolveVendor($request);

        return response()->json([
            'data' => $vendor,
        ]);
    }

    public function offers(Request $request): JsonResponse
    {
        $vendor = $this->resolveVendor($request);
        $offers = VendorOffer::with('variant.product')
            ->where('vendor_id', $vendor->id)
            ->paginate(20);

        return response()->json([
            'data' => $offers,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $vendor = $this->resolveVendor($request);
        $orders = VendorOrder::with(['items.orderItem', 'order'])
            ->where('vendor_id', $vendor->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function payouts(Request $request): JsonResponse
    {
        $vendor = $this->resolveVendor($request);
        $payouts = VendorPayout::where('vendor_id', $vendor->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $payouts,
        ]);
    }
}
