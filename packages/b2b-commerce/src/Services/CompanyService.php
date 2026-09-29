<?php

namespace Packages\B2BCommerce\Services;

use App\Models\User;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;

class CompanyService
{
    public function createCompany(array $attributes): Company
    {
        if (! isset($attributes['credit_balance']) && isset($attributes['credit_limit'])) {
            $attributes['credit_balance'] = $attributes['credit_limit'];
        }

        return Company::create($attributes);
    }

    public function attachUser(Company $company, User $user, string $role = 'buyer', ?int $spendingLimit = null): CompanyUser
    {
        return CompanyUser::updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'spending_limit' => $spendingLimit,
                'is_active' => true,
            ]
        );
    }

    public function updateCreditLimit(Company $company, int $newLimitInCents): Company
    {
        $diff = $newLimitInCents - $company->credit_limit;
        $newBalance = max(0, $company->credit_balance + $diff);

        $company->update([
            'credit_limit' => $newLimitInCents,
            'credit_balance' => $newBalance,
        ]);

        return $company;
    }
}
