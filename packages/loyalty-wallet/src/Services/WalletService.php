<?php

namespace Packages\LoyaltyWallet\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Packages\LoyaltyWallet\Models\WalletAccount;
use Packages\LoyaltyWallet\Models\WalletLedgerEntry;
use RuntimeException;

class WalletService
{
    /**
     * Retrieve or initialize a customer's wallet account.
     */
    public function getOrCreateWallet(User $user): WalletAccount
    {
        return WalletAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'currency' => 'INR',
                'status' => 'active',
            ]
        );
    }

    /**
     * Credit funds into the wallet and record an immutable ledger movement.
     */
    public function credit(
        WalletAccount $wallet,
        int $amount,
        string $referenceType,
        ?string $referenceId,
        string $description
    ): WalletLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Credit amount must be positive, [{$amount}] given.");
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            // Pessimistic row-level locking
            $lockedWallet = WalletAccount::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if (! $lockedWallet->isActive()) {
                throw new RuntimeException("Wallet account #{$lockedWallet->id} is not active.");
            }

            $lockedWallet->balance += $amount;
            $lockedWallet->save();

            return WalletLedgerEntry::create([
                'wallet_account_id' => $lockedWallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $lockedWallet->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Debit funds from the wallet after validating sufficient balance.
     */
    public function debit(
        WalletAccount $wallet,
        int $amount,
        string $referenceType,
        ?string $referenceId,
        string $description
    ): WalletLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Debit amount must be positive, [{$amount}] given.");
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            // Pessimistic row-level locking
            $lockedWallet = WalletAccount::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if (! $lockedWallet->isActive()) {
                throw new RuntimeException("Wallet account #{$lockedWallet->id} is not active.");
            }

            if ($lockedWallet->balance < $amount) {
                throw new RuntimeException("Insufficient wallet balance. Required: {$amount}, Available: {$lockedWallet->balance}.");
            }

            $lockedWallet->balance -= $amount;
            $lockedWallet->save();

            return WalletLedgerEntry::create([
                'wallet_account_id' => $lockedWallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $lockedWallet->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }
}
