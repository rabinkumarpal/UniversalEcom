<?php

namespace Packages\LoyaltyWallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletAccount extends Model
{
    protected $table = 'wallet_accounts';

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_account_id')->orderByDesc('created_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
