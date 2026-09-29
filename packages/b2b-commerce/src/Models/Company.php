<?php

namespace Packages\B2BCommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_group_id',
        'name',
        'company_code',
        'tax_id',
        'status',
        'credit_limit',
        'credit_balance',
        'payment_terms_days',
        'billing_address',
        'shipping_address',
    ];

    protected $attributes = [
        'status' => 'active',
        'credit_limit' => 0,
        'credit_balance' => 0,
        'payment_terms_days' => 30,
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'integer',
            'credit_balance' => 'integer',
            'payment_terms_days' => 'integer',
            'billing_address' => 'array',
            'shipping_address' => 'array',
        ];
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot(['role', 'spending_limit', 'is_active'])
            ->withTimestamps();
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(ContractPriceList::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasAvailableCredit(int $amountInCents): bool
    {
        return $this->credit_balance >= $amountInCents;
    }

    public function deductCredit(int $amountInCents): void
    {
        if ($amountInCents <= 0) {
            return;
        }

        if (! $this->hasAvailableCredit($amountInCents)) {
            throw new RuntimeException('Insufficient company credit balance. Required: ₹'.number_format($amountInCents / 100, 2).', Available: ₹'.number_format($this->credit_balance / 100, 2));
        }

        $this->decrement('credit_balance', $amountInCents);
    }

    public function restoreCredit(int $amountInCents): void
    {
        if ($amountInCents <= 0) {
            return;
        }

        $newBalance = min($this->credit_limit, $this->credit_balance + $amountInCents);
        $this->update(['credit_balance' => $newBalance]);
    }
}
