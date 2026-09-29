<?php

namespace Packages\B2BCommerce\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\PurchaseOrder;
use Packages\B2BCommerce\Services\B2BApprovalService;

class CustomerB2BController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('company')
            ->first();

        $isAdmin = $user && (
            $user->hasRole('admin') ||
            $user->hasRole('super-admin') ||
            $user->hasRole('super_admin') ||
            $user->hasRole('operations_manager')
        );

        if ((! $companyUser || ! $companyUser->company || ! $companyUser->company->isActive()) && $isAdmin) {
            $company = Company::where('status', 'active')->first();
            if (! $company) {
                $company = Company::create([
                    'name' => 'Acme Builders Infrastructure Ltd',
                    'company_code' => 'ACME-BLD',
                    'tax_id' => '29XYZAB1234C1Z9',
                    'credit_limit' => 100000000,
                    'credit_balance' => 85000000,
                    'payment_terms_days' => 30,
                    'status' => 'active',
                ]);
            }

            $companyUser = CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                ['role' => 'admin', 'is_active' => true, 'spending_limit' => 100000000]
            );
            $companyUser->setRelation('company', $company);
        }

        if (! $companyUser || ! $companyUser->company || ! $companyUser->company->isActive()) {
            return redirect()->route('account.orders')->with('error', 'Your account is not linked to an active corporate enterprise account.');
        }

        $company = $companyUser->company;
        $status = $request->query('status', 'all');

        $query = PurchaseOrder::where('company_id', $company->id)
            ->with(['order.items.variant.product', 'requester', 'approver'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();

        // If user is supervisor/admin, load pending approval requisitions
        $pendingApprovals = ($companyUser->isApprover())
            ? PurchaseOrder::where('company_id', $company->id)
                ->where('status', 'pending_approval')
                ->with(['order.items.variant.product', 'requester'])
                ->latest()
                ->get()
            : collect();

        // Calculate credit utilization metrics
        $creditLimit = $company->credit_limit;
        $creditBalance = $company->credit_balance;
        $creditUsed = max(0, $creditLimit - $creditBalance);
        $creditUsedPercentage = $creditLimit > 0 ? min(100, round(($creditUsed / $creditLimit) * 100)) : 0;

        return view('account.b2b', [
            'company' => $company,
            'companyUser' => $companyUser,
            'purchaseOrders' => $purchaseOrders,
            'pendingApprovals' => $pendingApprovals,
            'currentStatus' => $status,
            'creditLimit' => $creditLimit,
            'creditBalance' => $creditBalance,
            'creditUsed' => $creditUsed,
            'creditUsedPercentage' => $creditUsedPercentage,
        ]);
    }

    public function approve(Request $request, int $id, B2BApprovalService $approvalService): RedirectResponse
    {
        $user = $request->user();
        $po = PurchaseOrder::findOrFail($id);

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('company_id', $po->company_id)
            ->where('is_active', true)
            ->first();

        if (! $companyUser || ! $companyUser->isApprover()) {
            abort(403, 'You do not have authorization to approve purchase orders for this corporate account.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $approvalService->approvePurchaseOrder($po, $user, $validated['notes'] ?? 'Approved via Corporate B2B Account Portal.');

        return back()->with('success', "Purchase Order #{$po->po_number} has been approved and confirmed for fulfillment.");
    }

    public function reject(Request $request, int $id, B2BApprovalService $approvalService): RedirectResponse
    {
        $user = $request->user();
        $po = PurchaseOrder::findOrFail($id);

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('company_id', $po->company_id)
            ->where('is_active', true)
            ->first();

        if (! $companyUser || ! $companyUser->isApprover()) {
            abort(403, 'You do not have authorization to reject purchase orders for this corporate account.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $approvalService->rejectPurchaseOrder($po, $user, $validated['notes'] ?? 'Rejected by company supervisor.');

        return back()->with('success', "Purchase Order #{$po->po_number} has been rejected and the company credit line has been restored.");
    }

    public function showPo(Request $request, string $poNumber): View
    {
        $user = $request->user();
        $po = PurchaseOrder::where('po_number', $poNumber)
            ->with(['company', 'order.items.variant.product', 'order.user', 'requester', 'approver'])
            ->firstOrFail();

        $isCompanyMember = CompanyUser::where('user_id', $user->id)
            ->where('company_id', $po->company_id)
            ->where('is_active', true)
            ->exists();

        $isAdmin = $user && (
            $user->hasRole('admin') ||
            $user->hasRole('super-admin') ||
            $user->hasRole('super_admin') ||
            $user->hasRole('operations_manager')
        );

        if (! $isCompanyMember && ! $isAdmin) {
            abort(403, 'Unauthorized access to this corporate purchase order document.');
        }

        return view('account.po_document', [
            'po' => $po,
            'company' => $po->company,
            'order' => $po->order,
        ]);
    }
}
