<?php

namespace Packages\B2BCommerce\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\B2BCommerce\Models\PurchaseOrder;
use Packages\B2BCommerce\Services\B2BApprovalService;

class AdminB2BPurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = PurchaseOrder::query();

        if ($request->filled('company_id')) {
            $baseQuery->where('company_id', $request->query('company_id'));
        }

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending_approval' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        $query = (clone $baseQuery)->with(['company', 'requester', 'approver', 'order']);

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        $purchaseOrders = $query->latest()->paginate(15)->withQueryString();

        return view('admin.b2b.purchase-orders', [
            'purchaseOrders' => $purchaseOrders,
            'currentStatus' => $request->query('status', 'all'),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function approve(Request $request, int $id, B2BApprovalService $approvalService): RedirectResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        try {
            $approvalService->approvePurchaseOrder(
                $po,
                $request->user(),
                $request->input('notes', 'Admin supervisor approved.')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Purchase Order #{$po->po_number} successfully approved and confirmed.");
    }

    public function reject(Request $request, int $id, B2BApprovalService $approvalService): RedirectResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        try {
            $approvalService->rejectPurchaseOrder(
                $po,
                $request->user(),
                $request->input('notes', 'Admin supervisor rejected.')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Purchase Order #{$po->po_number} rejected. Company credit line restored.");
    }
}
