<?php

namespace Packages\LoyaltyWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    public $timestamps = false;

    protected $table = 'wallet_ledger_entries';

    protected $fillable = [
        'wallet_account_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }
}
