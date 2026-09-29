<?php

namespace Packages\VendorMarketplace\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorPayout;

class VendorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $totalSales = (int) VendorOrder::where('vendor_id', $vendor->id)->sum('subtotal');
        $commissionPaid = (int) VendorOrder::where('vendor_id', $vendor->id)->sum('commission_amount');
        $netPayout = (int) VendorOrder::where('vendor_id', $vendor->id)->sum('vendor_payout');

        $totalCommitted = (int) VendorPayout::where('vendor_id', $vendor->id)
            ->whereIn('status', ['paid', 'approved', 'pending'])
            ->sum('amount');

        $availablePayoutBalance = max(0, $netPayout - $totalCommitted);

        $pendingFulfillment = VendorOrder::where('vendor_id', $vendor->id)
            ->whereIn('status', ['confirmed', 'picking', 'packed'])
            ->count();

        $activeOffersCount = VendorOffer::where('vendor_id', $vendor->id)
            ->where('status', 'approved')
            ->count();

        $recentOrders = VendorOrder::where('vendor_id', $vendor->id)
            ->with(['order', 'items'])
            ->latest()
            ->take(6)
            ->get();

        return view('vendor-marketplace::dashboard', [
            'vendor' => $vendor,
            'totalSales' => $totalSales,
            'commissionPaid' => $commissionPaid,
            'netPayout' => $netPayout,
            'availablePayoutBalance' => $availablePayoutBalance,
            'pendingFulfillment' => $pendingFulfillment,
            'activeOffersCount' => $activeOffersCount,
            'recentOrders' => $recentOrders,
        ]);
    }
}
