<?php

namespace Packages\PaymentGateways\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    protected $table = 'payment_reconciliations';

    protected $fillable = [
        'reconciliation_number',
        'order_id',
        'payment_id',
        'method',
        'expected_amount',
        'collected_amount',
        'advance_amount',
        'balance_amount',
        'settlement_status',
        'collected_by_user_id',
        'verified_by_user_id',
        'verified_at',
        'discrepancy_reason',
        'reference_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'integer',
            'collected_amount' => 'integer',
            'advance_amount' => 'integer',
            'balance_amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Record driver collection of cash/UPI upon delivery.
     */
    public function recordCollection(int $amount, ?User $collector = null, ?string $reference = null): self
    {
        $newCollected = $this->collected_amount + $amount;
        $remaining = max(0, $this->expected_amount - $newCollected);

        $status = $newCollected >= $this->expected_amount ? 'settled' : 'partial';

        $this->update([
            'collected_amount' => $newCollected,
            'balance_amount' => $remaining,
            'settlement_status' => $status,
            'collected_by_user_id' => $collector?->id ?? $this->collected_by_user_id,
            'reference_number' => $reference ?? $this->reference_number,
        ]);

        return $this;
    }

    /**
     * Finalize finance settlement with reference verification.
     */
    public function markSettled(int $amount, ?string $referenceNumber = null, ?User $verifier = null): self
    {
        $this->update([
            'collected_amount' => $amount,
            'balance_amount' => max(0, $this->expected_amount - $amount),
            'settlement_status' => 'settled',
            'reference_number' => $referenceNumber ?? $this->reference_number,
            'verified_by_user_id' => $verifier?->id,
            'verified_at' => now(),
        ]);

        // Mark associated order and payment as captured if not already
        if ($this->order && $this->order->payment_status !== 'captured') {
            $this->order->update(['payment_status' => 'captured']);
        }
        if ($this->payment && $this->payment->status !== 'captured') {
            $this->payment->update(['status' => 'captured']);
        }

        return $this;
    }

    /**
     * Flag discrepancy in collections (e.g. short cash, fake currency, uncollected).
     */
    public function flagDiscrepancy(string $reason, ?User $verifier = null): self
    {
        $this->update([
            'settlement_status' => 'discrepancy',
            'discrepancy_reason' => $reason,
            'verified_by_user_id' => $verifier?->id,
            'verified_at' => now(),
        ]);

        return $this;
    }
}
