<?php

namespace Packages\VendorMarketplace\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Packages\VendorMarketplace\Models\VendorUser;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('vendor.login')->with('error', 'Please log in to access the vendor portal.');
        }

        $vendorUser = VendorUser::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with('vendor')
            ->first();

        if (! $vendorUser || ! $vendorUser->vendor) {
            abort(403, 'Unauthorized. Your account is not associated with an authorized vendor profile.');
        }

        $vendor = $vendorUser->vendor;

        if ($vendor->approval_status === 'pending') {
            return response()->view('vendor-marketplace::auth.status', [
                'vendor' => $vendor,
                'status' => 'pending',
                'message' => 'Your vendor application is currently under review by platform administrators.',
            ], 403);
        }

        if ($vendor->approval_status === 'rejected' || $vendor->status === 'suspended') {
            return response()->view('vendor-marketplace::auth.status', [
                'vendor' => $vendor,
                'status' => 'suspended',
                'message' => 'Your vendor account is suspended or rejected. Please contact administrator support.',
            ], 403);
        }

        // Share vendor in request attributes for controllers
        $request->attributes->set('vendor', $vendor);
        $request->attributes->set('vendorUser', $vendorUser);

        return $next($request);
    }
}
