<?php

namespace Packages\VendorMarketplace\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorPayout;

class VendorPayoutController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $totalEarned = (int) VendorOrder::where('vendor_id', $vendor->id)->sum('vendor_payout');
        $totalCommitted = (int) VendorPayout::where('vendor_id', $vendor->id)
            ->whereIn('status', ['paid', 'approved', 'pending'])
            ->sum('amount');

        $unsettledBalance = max(0, $totalEarned - $totalCommitted);

        $payouts = VendorPayout::where('vendor_id', $vendor->id)
            ->latest()
            ->paginate(15);

        return view('vendor-marketplace::payouts.index', [
            'vendor' => $vendor,
            'totalEarned' => $totalEarned,
            'totalCommitted' => $totalCommitted,
            'unsettledBalance' => $unsettledBalance,
            'payouts' => $payouts,
        ]);
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $totalEarned = (int) VendorOrder::where('vendor_id', $vendor->id)->sum('vendor_payout');
        $totalCommitted = (int) VendorPayout::where('vendor_id', $vendor->id)
            ->whereIn('status', ['paid', 'approved', 'pending'])
            ->sum('amount');

        $unsettledBalance = max(0, $totalEarned - $totalCommitted);

        if ($unsettledBalance < 100) { // minimum ₹1.00
            return back()->with('error', 'You do not have any eligible unsettled earnings balance to request.');
        }

        $payout = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'payout_number' => 'VP-'.strtoupper(Str::random(8)),
            'amount' => $unsettledBalance,
            'status' => 'pending',
            'period_start' => now()->subDays(30),
            'period_end' => now(),
        ]);

        $formattedAmount = '₹'.number_format($payout->amount / 100, 2);

        return back()->with('success', "Payout request {$payout->payout_number} for {$formattedAmount} submitted successfully.");
    }
}
