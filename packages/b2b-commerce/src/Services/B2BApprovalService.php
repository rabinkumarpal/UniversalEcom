<?php

namespace Packages\B2BCommerce\Services;

use App\Domain\Inventory\InventoryService;
use App\Models\OrderStatusHistory;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\PurchaseOrder;
use RuntimeException;

class B2BApprovalService
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function canUserApprove(PurchaseOrder $po, User $user): bool
    {
        // Platform administrative roles can approve
        if ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('super_admin') || $user->hasRole('operations_manager')) {
            return true;
        }

        $companyUser = CompanyUser::where('company_id', $po->company_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $companyUser && $companyUser->isApprover();
    }

    public function approvePurchaseOrder(PurchaseOrder $po, User $approver, ?string $notes = null): PurchaseOrder
    {
        if (! $this->canUserApprove($po, $approver)) {
            throw new RuntimeException('User does not have authorization to approve purchase orders for this corporate account.');
        }

        if ($po->status !== 'pending_approval') {
            throw new RuntimeException("Purchase order #{$po->po_number} is already in '{$po->status}' status.");
        }

        return DB::transaction(function () use ($po, $approver, $notes) {
            $po->update([
                'status' => 'approved',
                'approver_user_id' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $notes ?? 'Approved by supervisor.',
            ]);

            $order = $po->order;
            if ($order) {
                $previousStatus = $order->status;
                $order->status = 'confirmed';
                $order->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'confirmed',
                    'note' => 'Corporate Purchase Order #'.$po->po_number.' approved by '.$approver->name.($notes ? ': '.$notes : ''),
                    'user_id' => $approver->id,
                ]);

                // Transition payment record to authorized
                $payment = $order->payments()->where('gateway', 'purchase_order')->first();
                $payment?->update(['status' => 'authorized']);

                // Consume inventory stock reservations
                $reservations = StockReservation::where('order_id', $order->id)
                    ->where('status', 'active')
                    ->get();

                foreach ($reservations as $res) {
                    $this->inventoryService->consumeReservation($res);
                }

                // Update invoice
                $order->invoice?->update(['status' => 'issued']);
            }

            return $po->fresh(['company', 'order', 'requester', 'approver']);
        });
    }

    public function rejectPurchaseOrder(PurchaseOrder $po, User $approver, ?string $notes = null): PurchaseOrder
    {
        if (! $this->canUserApprove($po, $approver)) {
            throw new RuntimeException('User does not have authorization to reject purchase orders for this corporate account.');
        }

        if ($po->status !== 'pending_approval') {
            throw new RuntimeException("Purchase order #{$po->po_number} is already in '{$po->status}' status.");
        }

        return DB::transaction(function () use ($po, $approver, $notes) {
            $po->update([
                'status' => 'rejected',
                'approver_user_id' => $approver->id,
                'rejected_at' => now(),
                'approval_notes' => $notes ?? 'Rejected by supervisor.',
            ]);

            // Restore company credit balance
            $po->company->restoreCredit($po->amount);

            $order = $po->order;
            if ($order) {
                $previousStatus = $order->status;
                $order->status = 'cancelled';
                $order->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'cancelled',
                    'note' => 'Corporate Purchase Order #'.$po->po_number.' rejected by '.$approver->name.($notes ? ': '.$notes : ''),
                    'user_id' => $approver->id,
                ]);

                // Release inventory stock reservations
                $reservations = StockReservation::where('order_id', $order->id)
                    ->where('status', 'active')
                    ->get();

                foreach ($reservations as $res) {
                    $this->inventoryService->releaseReservation($res);
                }
            }

            return $po->fresh(['company', 'order', 'requester', 'approver']);
        });
    }
}
