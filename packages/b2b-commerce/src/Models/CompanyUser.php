<?php

namespace Packages\B2BCommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'spending_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'spending_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApprover(): bool
    {
        return in_array($this->role, ['admin', 'approver'], true);
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    /**
     * Determine if an order amount requires supervisor approval.
     */
    public function requiresApproval(int $orderTotal): bool
    {
        if ($this->isAdmin() || $this->role === 'approver') {
            return false;
        }

        if ($this->spending_limit !== null && $orderTotal > $this->spending_limit) {
            return true;
        }

        return false;
    }
}
