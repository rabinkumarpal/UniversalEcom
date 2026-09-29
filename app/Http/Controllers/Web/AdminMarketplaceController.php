<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorPayout;

class AdminMarketplaceController extends Controller
{
    public function vendors(Request $request): View
    {
        $baseQuery = Vendor::query();

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $baseQuery->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending_approval' => (clone $baseQuery)->where('approval_status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('approval_status', 'approved')->where('status', 'active')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
        ];

        $query = (clone $baseQuery)->withCount(['offers', 'vendorOrders']);

        $tab = $request->query('tab', 'all');
        if ($tab === 'pending_approval') {
            $query->where('approval_status', 'pending');
        } elseif ($tab === 'approved') {
            $query->where('approval_status', 'approved')->where('status', 'active');
        } elseif ($tab === 'suspended') {
            $query->where('status', 'suspended');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->query('approval_status'));
        }

        $vendors = $query->latest()->paginate(15)->withQueryString();

        return view('admin.marketplace.vendors', [
            'vendors' => $vendors,
            'currentTab' => $tab,
            'statusCounts' => $statusCounts,
            'search' => $request->query('search', ''),
        ]);
    }

    public function updateVendor(Request $request, int $id): RedirectResponse
    {
        $vendor = Vendor::findOrFail($id);

        if ($request->filled('quick_action')) {
            $action = $request->input('quick_action');
            if ($action === 'approve') {
                $vendor->update([
                    'approval_status' => 'approved',
                    'status' => 'active',
                    'approved_at' => $vendor->approved_at ?? now(),
                ]);

                return back()->with('success', "Vendor [{$vendor->display_name}] successfully approved and activated.");
            } elseif ($action === 'reject') {
                $vendor->update([
                    'approval_status' => 'rejected',
                    'status' => 'suspended',
                ]);

                return back()->with('success', "Vendor [{$vendor->display_name}] application rejected.");
            } elseif ($action === 'suspend') {
                $vendor->update(['status' => 'suspended']);

                return back()->with('success', "Vendor [{$vendor->display_name}] suspended.");
            } elseif ($action === 'activate') {
                $vendor->update(['status' => 'active']);

                return back()->with('success', "Vendor [{$vendor->display_name}] activated.");
            }
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,active,suspended'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'commission_rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($validated['approval_status'] === 'approved' && ! $vendor->approved_at) {
            $vendor->approved_at = now();
        }

        $vendor->update($validated);

        return back()->with('success', "Vendor [{$vendor->display_name}] status updated to {$vendor->status} / {$vendor->approval_status}.");
    }

    public function payouts(Request $request): View
    {
        $baseQuery = VendorPayout::query();

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $baseQuery->where(function ($q) use ($search) {
                $q->where('payout_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('display_name', 'like', "%{$search}%")
                            ->orWhere('legal_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('vendor_id')) {
            $baseQuery->where('vendor_id', $request->query('vendor_id'));
        }

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
        ];

        // Financial KPIs (accounting for current filters)
        $kpiTotalDisbursed = (clone $baseQuery)->where('status', 'paid')->sum('amount');
        $kpiPendingSettlement = (clone $baseQuery)->whereIn('status', ['pending', 'approved'])->sum('amount');
        $kpiTotalStatements = (clone $baseQuery)->count();

        $query = (clone $baseQuery)->with('vendor');

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        $payouts = $query->latest()->paginate(15)->withQueryString();
        $vendors = Vendor::orderBy('display_name')->get(['id', 'display_name', 'legal_name']);

        return view('admin.marketplace.payouts', [
            'payouts' => $payouts,
            'vendors' => $vendors,
            'currentStatus' => $request->query('status', 'all'),
            'statusCounts' => $statusCounts,
            'search' => $request->query('search', ''),
            'selectedVendorId' => $request->query('vendor_id', ''),
            'kpis' => [
                'total_statements' => $kpiTotalStatements,
                'total_disbursed' => $kpiTotalDisbursed,
                'pending_settlement' => $kpiPendingSettlement,
            ],
        ]);
    }

    public function approvePayout(int $id): RedirectResponse
    {
        $payout = VendorPayout::findOrFail($id);

        if ($payout->status === 'pending') {
            $payout->update(['status' => 'approved']);

            return back()->with('success', "Payout statement #{$payout->payout_number} approved for disbursement.");
        }

        return back()->with('info', "Payout statement #{$payout->payout_number} is already {$payout->status}.");
    }

    public function settlePayout(Request $request, int $id): RedirectResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:100'],
        ]);

        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $validated['payment_reference'],
        ]);

        return back()->with('success', "Payout statement #{$payout->payout_number} marked as settled with ref: {$payout->payment_reference}.");
    }
}
