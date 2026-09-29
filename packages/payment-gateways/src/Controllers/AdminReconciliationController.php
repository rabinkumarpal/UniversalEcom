<?php

namespace Packages\PaymentGateways\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\PaymentGateways\Models\PaymentGatewayConfig;
use Packages\PaymentGateways\Models\PaymentReconciliation;
use Packages\PaymentGateways\Services\PaymentReconciliationService;

class AdminReconciliationController extends Controller
{
    public function __construct(
        protected PaymentReconciliationService $reconciliationService
    ) {}

    /**
     * Display financial reconciliation ledger and analytics.
     */
    public function index(Request $request): View
    {
        $query = PaymentReconciliation::with(['order', 'payment', 'collectedBy', 'verifiedBy'])->latest('id');

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('settlement_status', $status);
            }
        }

        if ($method = $request->input('method')) {
            if ($method !== 'all') {
                $query->where('method', $method);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reconciliation_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        $records = $query->paginate(15)->withQueryString();

        // Metrics
        $totalExpected = PaymentReconciliation::sum('expected_amount');
        $totalCollected = PaymentReconciliation::sum('collected_amount');
        $totalBalance = PaymentReconciliation::sum('balance_amount');
        $discrepancyCount = PaymentReconciliation::where('settlement_status', 'discrepancy')->count();

        return view('payment-gateways::admin.reconciliation', compact(
            'records',
            'totalExpected',
            'totalCollected',
            'totalBalance',
            'discrepancyCount'
        ));
    }

    /**
     * Supervisor confirms settlement of reconciliation record.
     */
    public function settle(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'required|string',
        ]);

        $record = PaymentReconciliation::findOrFail($id);
        $amountInPaise = (int) round($validated['amount'] * 100);

        $this->reconciliationService->settle(
            $record,
            $amountInPaise,
            $validated['reference_number'],
            $request->user()
        );

        return redirect()->back()->with('success', "Reconciliation record #{$record->reconciliation_number} settled successfully.");
    }

    /**
     * Supervisor flags collection discrepancy.
     */
    public function discrepancy(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $record = PaymentReconciliation::findOrFail($id);

        $this->reconciliationService->flagDiscrepancy(
            $record,
            $validated['reason'],
            $request->user()
        );

        return redirect()->back()->with('warning', "Discrepancy recorded for reconciliation #{$record->reconciliation_number}.");
    }

    /**
     * Display Gateway Credentials & Settings Manager.
     */
    public function gateways(): View
    {
        $razorpay = PaymentGatewayConfig::getRazorpayConfig();
        $cod = PaymentGatewayConfig::getCodConfig();

        return view('payment-gateways::admin.gateways', compact('razorpay', 'cod'));
    }

    /**
     * Update gateway configuration.
     */
    public function updateGateway(Request $request, string $gateway): RedirectResponse
    {
        if ($gateway === 'razorpay') {
            $validated = $request->validate([
                'key_id' => 'required|string',
                'key_secret' => 'required|string',
                'webhook_secret' => 'required|string',
                'is_active' => 'nullable|boolean',
                'is_test_mode' => 'nullable|boolean',
            ]);

            $config = PaymentGatewayConfig::getRazorpayConfig();
            $config->update([
                'credentials' => [
                    'key_id' => $validated['key_id'],
                    'key_secret' => $validated['key_secret'],
                    'webhook_secret' => $validated['webhook_secret'],
                ],
                'is_active' => $request->has('is_active'),
                'is_test_mode' => $request->has('is_test_mode'),
            ]);
        } elseif ($gateway === 'advanced_cod') {
            $validated = $request->validate([
                'advance_deposit_percentage' => 'required|numeric|min:0|max:100',
                'advance_deposit_threshold' => 'required|numeric|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $config = PaymentGatewayConfig::getCodConfig();
            $config->update([
                'settings' => [
                    'advance_deposit_percentage' => (int) $validated['advance_deposit_percentage'],
                    'advance_deposit_threshold' => (int) round($validated['advance_deposit_threshold'] * 100),
                ],
                'is_active' => $request->has('is_active'),
            ]);
        }

        return redirect()->back()->with('success', 'Gateway settings updated successfully.');
    }
}
