<?php

namespace Packages\B2BCommerce\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\CustomerGroup;
use Packages\B2BCommerce\Services\CompanyService;

class AdminB2BCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::with(['customerGroup'])
            ->withCount(['companyUsers', 'purchaseOrders']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_code', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(15)->withQueryString();
        $customerGroups = CustomerGroup::orderBy('name')->get();

        return view('admin.b2b.companies', [
            'companies' => $companies,
            'customerGroups' => $customerGroups,
            'currentStatus' => $request->query('status', 'all'),
        ]);
    }

    public function show(int $id): View
    {
        $company = Company::with([
            'customerGroup',
            'companyUsers.user',
            'priceLists.variantPrices.variant.product',
            'purchaseOrders.requester',
            'purchaseOrders.approver',
            'purchaseOrders.order',
        ])->findOrFail($id);

        $availableUsers = User::orderBy('name')->take(100)->get();

        return view('admin.b2b.company-show', [
            'company' => $company,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(Request $request, CompanyService $companyService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50', 'unique:companies,company_code'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'credit_limit_in_rupees' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:180'],
        ]);

        $creditLimitPaise = (int) round($validated['credit_limit_in_rupees'] * 100);

        $company = $companyService->createCompany([
            'name' => $validated['name'],
            'company_code' => strtoupper($validated['company_code']),
            'tax_id' => $validated['tax_id'] ?? null,
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'status' => 'active',
            'credit_limit' => $creditLimitPaise,
            'credit_balance' => $creditLimitPaise,
            'payment_terms_days' => $validated['payment_terms_days'],
        ]);

        return redirect()->route('admin.b2b.companies.show', $company->id)
            ->with('success', "Company [{$company->name}] successfully registered with ₹".number_format($validated['credit_limit_in_rupees'], 2).' credit limit.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended,pending_approval'],
        ]);

        $company->update(['status' => $validated['status']]);

        return back()->with('success', "Company [{$company->name}] status updated to {$company->status}.");
    }

    public function updateCreditLimit(Request $request, int $id, CompanyService $companyService): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'credit_limit_in_rupees' => ['required', 'numeric', 'min:0'],
        ]);

        $newLimitPaise = (int) round($validated['credit_limit_in_rupees'] * 100);
        $companyService->updateCreditLimit($company, $newLimitPaise);

        return back()->with('success', "Credit line for [{$company->name}] updated to ₹".number_format($validated['credit_limit_in_rupees'], 2).'.');
    }

    public function assignUser(Request $request, int $id, CompanyService $companyService): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'user_id' => ['required_without:email', 'nullable', 'exists:users,id'],
            'email' => ['required_without:user_id', 'nullable', 'email', 'exists:users,email'],
            'role' => ['required', 'in:admin,approver,buyer'],
            'spending_limit_in_rupees' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = ! empty($validated['user_id'])
            ? User::findOrFail($validated['user_id'])
            : User::where('email', $validated['email'])->firstOrFail();

        $spendingLimitCents = isset($validated['spending_limit_in_rupees']) && $validated['spending_limit_in_rupees'] !== ''
            ? (int) round((float) $validated['spending_limit_in_rupees'] * 100)
            : null;

        $companyService->attachUser($company, $user, $validated['role'], $spendingLimitCents);

        return back()->with('success', "User [{$user->name}] successfully assigned to [{$company->name}] as [{$validated['role']}].");
    }

    public function removeUser(int $id, int $userId): RedirectResponse
    {
        $company = Company::findOrFail($id);
        CompanyUser::where('company_id', $company->id)->where('user_id', $userId)->delete();

        return back()->with('success', "User detached from [{$company->name}].");
    }
}
