<?php

namespace Tests\Feature;

use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\PaymentGateways\Gateways\AdvancedCodPaymentGateway;
use Packages\PaymentGateways\Gateways\RazorpayPaymentGateway;
use Packages\PaymentGateways\Models\PaymentReconciliation;
use Packages\PaymentGateways\PaymentGatewaysAddon;
use Packages\PaymentGateways\Services\PaymentReconciliationService;
use Packages\PaymentGateways\Services\RazorpayService;
use Tests\TestCase;

class PaymentGatewaysTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $customerUser;

    protected User $driverUser;

    protected Order $sampleOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Users
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $driverRole = Role::firstOrCreate(['slug' => 'driver'], ['name' => 'Driver']);

        $this->adminUser = User::factory()->create([
            'name' => 'Finance Director',
            'email' => 'finance.dir@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->customerUser = User::factory()->create([
            'name' => 'Amit Builders',
            'email' => 'amit.builders@example.test',
            'phone' => '9820011223',
        ]);

        $this->driverUser = User::factory()->create([
            'name' => 'Vijay Logistics Driver',
            'email' => 'vijay.fleet@example.test',
            'phone' => '9820099887',
        ]);
        $this->driverUser->roles()->attach($driverRole);

        // 2. Sample Order
        $this->sampleOrder = Order::create([
            'order_number' => 'ORD-PAY-2026-001',
            'user_id' => $this->customerUser->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 4500000,
            'tax_total' => 810000,
            'delivery_fee' => 90000,
            'discount_total' => 0,
            'grand_total' => 5400000, // ₹54,000.00
            'shipping_address_snapshot' => [
                'first_name' => 'Amit',
                'last_name' => 'Patel',
                'phone' => '9820011223',
                'address_line1' => 'Sector 18, Commercial Hub',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122001',
            ],
            'billing_address_snapshot' => [
                'first_name' => 'Amit',
                'last_name' => 'Patel',
                'phone' => '9820011223',
                'address_line1' => 'Sector 18, Commercial Hub',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122001',
            ],
            'placed_at' => now(),
        ]);
    }

    /**
     * Test 1: PaymentGateways addon is registered in AddonRegistry.
     */
    public function test_payment_gateways_addon_is_registered_in_addon_registry(): void
    {
        $registry = app(AddonRegistry::class);
        $addon = $registry->get('payment-gateways');

        $this->assertNotNull($addon);
        $this->assertInstanceOf(PaymentGatewaysAddon::class, $addon);
        $this->assertTrue($addon->isEnabled());
        $this->assertEquals('1.0.0', $addon->version());
    }

    /**
     * Test 2: Gateways registered in PaymentGatewayRegistry.
     */
    public function test_razorpay_and_advanced_cod_gateways_registered_in_payment_gateway_registry(): void
    {
        $registry = app(PaymentGatewayRegistry::class);

        $this->assertTrue($registry->has('razorpay'));
        $this->assertInstanceOf(RazorpayPaymentGateway::class, $registry->get('razorpay'));

        $this->assertTrue($registry->has('advanced_cod'));
        $this->assertInstanceOf(AdvancedCodPaymentGateway::class, $registry->get('advanced_cod'));
    }

    /**
     * Test 3: Razorpay gateway creates payment order payload.
     */
    public function test_razorpay_gateway_creates_payment_order_payload(): void
    {
        $gateway = app(RazorpayPaymentGateway::class);
        $payload = $gateway->createPayment($this->sampleOrder);

        $this->assertEquals('razorpay', $payload['gateway']);
        $this->assertStringStartsWith('order_', $payload['razorpay_order_id']);
        $this->assertEquals(5400000, $payload['amount']);
        $this->assertEquals('INR', $payload['currency']);
        $this->assertEquals('Amit Patel', $payload['prefill']['name']);
        $this->assertEquals('9820011223', $payload['prefill']['contact']);

        // Assert database records
        $payment = Payment::where('order_id', $this->sampleOrder->id)
            ->where('gateway', 'razorpay')
            ->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);

        $reconciliation = PaymentReconciliation::where('order_id', $this->sampleOrder->id)->first();
        $this->assertNotNull($reconciliation);
        $this->assertEquals(5400000, $reconciliation->expected_amount);
        $this->assertEquals('pending', $reconciliation->settlement_status);
    }

    /**
     * Test 4: Razorpay cryptographic signature verification succeeds with valid HMAC.
     */
    public function test_razorpay_signature_verification_succeeds_with_valid_hmac(): void
    {
        $gateway = app(RazorpayPaymentGateway::class);
        $razorpayService = app(RazorpayService::class);

        $payload = $gateway->createPayment($this->sampleOrder);
        $payment = Payment::find($payload['payment_id']);

        $rzpOrderId = $payload['razorpay_order_id'];
        $rzpPaymentId = 'pay_test_'.bin2hex(random_bytes(6));
        $validSignature = hash_hmac('sha256', $rzpOrderId.'|'.$rzpPaymentId, $razorpayService->getKeySecret());

        $verified = $gateway->verifyPayment($payment, [
            'razorpay_order_id' => $rzpOrderId,
            'razorpay_payment_id' => $rzpPaymentId,
            'razorpay_signature' => $validSignature,
        ]);

        $this->assertTrue($verified);
        $this->assertEquals('captured', $payment->fresh()->status);
        $this->assertEquals($rzpPaymentId, $payment->fresh()->transaction_id);
        $this->assertEquals('captured', $this->sampleOrder->fresh()->payment_status);
        $this->assertEquals('confirmed', $this->sampleOrder->fresh()->status);

        // Assert reconciliation settled
        $reconciliation = PaymentReconciliation::where('order_id', $this->sampleOrder->id)->first();
        $this->assertEquals('settled', $reconciliation->fresh()->settlement_status);
        $this->assertEquals(5400000, $reconciliation->fresh()->collected_amount);
        $this->assertEquals(0, $reconciliation->fresh()->balance_amount);
    }

    /**
     * Test 5: Razorpay signature verification fails with tampered payload.
     */
    public function test_razorpay_signature_verification_fails_with_tampered_payload(): void
    {
        $gateway = app(RazorpayPaymentGateway::class);
        $payload = $gateway->createPayment($this->sampleOrder);
        $payment = Payment::find($payload['payment_id']);

        $rzpOrderId = $payload['razorpay_order_id'];
        $rzpPaymentId = 'pay_tampered_123';
        $invalidSignature = 'forged_fake_signature_hash_0000000';

        $verified = $gateway->verifyPayment($payment, [
            'razorpay_order_id' => $rzpOrderId,
            'razorpay_payment_id' => $rzpPaymentId,
            'razorpay_signature' => $invalidSignature,
        ]);

        $this->assertFalse($verified);
        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertNotEquals('captured', $this->sampleOrder->fresh()->payment_status);
    }

    /**
     * Test 6: Inbound Razorpay webhook captures payment with idempotency protection.
     */
    public function test_razorpay_webhook_handles_payment_captured_with_idempotency(): void
    {
        $gateway = app(RazorpayPaymentGateway::class);
        $razorpayService = app(RazorpayService::class);
        $payload = $gateway->createPayment($this->sampleOrder);
        $payment = Payment::find($payload['payment_id']);

        $rzpOrderId = $payload['razorpay_order_id'];
        $rzpPaymentId = 'pay_wh_captured_9988';

        $webhookData = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $rzpPaymentId,
                        'order_id' => $rzpOrderId,
                        'amount' => 5400000,
                        'currency' => 'INR',
                        'status' => 'captured',
                    ],
                ],
            ],
        ];

        $rawBody = json_encode($webhookData);
        $signature = hash_hmac('sha256', $rawBody, $razorpayService->getWebhookSecret());

        // 1. First webhook dispatch
        $response = $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $rawBody);

        $response->assertOk();
        $this->assertEquals('captured', $payment->fresh()->status);
        $this->assertEquals($rzpPaymentId, $payment->fresh()->transaction_id);

        // 2. Duplicate/Replay webhook (Idempotency check)
        $replayResponse = $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $rawBody);

        $replayResponse->assertOk();
        $replayResponse->assertJson(['status' => 'idempotent_ok']);
    }

    /**
     * Test 7: Razorpay gateway processes refunds cleanly.
     */
    public function test_razorpay_gateway_processes_refund(): void
    {
        $gateway = app(RazorpayPaymentGateway::class);
        $payload = $gateway->createPayment($this->sampleOrder);
        $payment = Payment::find($payload['payment_id']);

        $payment->update([
            'status' => 'captured',
            'transaction_id' => 'pay_to_refund_1122',
        ]);

        $refundSuccess = $gateway->refund($payment, 2000000, 'Materials returned defective');

        $this->assertTrue($refundSuccess);
        $this->assertEquals('refunded', $payment->fresh()->status);
        $this->assertNotEmpty($payment->fresh()->payload['refund']);
        $this->assertEquals('Materials returned defective', $payment->fresh()->payload['refund_reason']);
    }

    /**
     * Test 8: Advanced COD calculates advance deposit for orders exceeding high-value threshold.
     */
    public function test_advanced_cod_calculates_required_advance_deposit_for_high_value_orders(): void
    {
        // Default threshold is ₹10,000 (1000000 paise). Sample order is ₹54,000 (5400000 paise).
        $gateway = app(AdvancedCodPaymentGateway::class);
        $payload = $gateway->createPayment($this->sampleOrder);

        $this->assertEquals('advanced_cod', $payload['gateway']);
        $this->assertTrue($payload['requires_advance']);

        // 15% of 54,000 = 8,100 (810000 paise)
        $this->assertEquals(810000, $payload['advance_amount']);
        // 85% balance = 45,900 (4590000 paise)
        $this->assertEquals(4590000, $payload['balance_amount']);

        // Verify reconciliation split record
        $reconciliation = PaymentReconciliation::where('order_id', $this->sampleOrder->id)->first();
        $this->assertNotNull($reconciliation);
        $this->assertEquals(810000, $reconciliation->advance_amount);
        $this->assertEquals(4590000, $reconciliation->balance_amount);
        $this->assertEquals('pending', $reconciliation->settlement_status);
    }

    /**
     * Test 9: Driver cash collection and admin settlement workflow.
     */
    public function test_driver_cash_collection_and_admin_settlement(): void
    {
        $recService = app(PaymentReconciliationService::class);
        $reconciliation = $recService->createForOrder($this->sampleOrder, 'advanced_cod');

        // 1. Driver records collection on site upon material unloading
        $recService->recordDriverRemittance(
            $reconciliation,
            4590000, // ₹45,900 cash received
            $this->driverUser,
            'CASH-DELIVERY-RECEIPT-01'
        );

        $fresh = $reconciliation->fresh();
        $this->assertEquals(4590000, $fresh->collected_amount);
        $this->assertEquals($this->driverUser->id, $fresh->collected_by_user_id);
        $this->assertEquals('CASH-DELIVERY-RECEIPT-01', $fresh->reference_number);

        // 2. Finance Supervisor settles the remittance into company bank vault
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.payments.reconciliation.settle', $reconciliation->id), [
                'amount' => 54000.00,
                'reference_number' => 'BANK-DEPOSIT-SLIP-7788',
            ]);

        $response->assertSessionHas('success');
        $settled = $reconciliation->fresh();
        $this->assertEquals('settled', $settled->settlement_status);
        $this->assertEquals('BANK-DEPOSIT-SLIP-7788', $settled->reference_number);
        $this->assertEquals($this->adminUser->id, $settled->verified_by_user_id);
        $this->assertNotNull($settled->verified_at);
    }

    /**
     * Test 10: Admin can flag discrepancy on short cash or uncollected balance.
     */
    public function test_admin_can_flag_reconciliation_discrepancy(): void
    {
        $recService = app(PaymentReconciliationService::class);
        $reconciliation = $recService->createForOrder($this->sampleOrder, 'cod');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.payments.reconciliation.discrepancy', $reconciliation->id), [
                'reason' => 'Customer paid ₹500 short cash on site; driver deducted from petty cash.',
            ]);

        $response->assertSessionHas('warning');
        $flagged = $reconciliation->fresh();
        $this->assertEquals('discrepancy', $flagged->settlement_status);
        $this->assertStringContainsString('short cash', $flagged->discrepancy_reason);
        $this->assertEquals($this->adminUser->id, $flagged->verified_by_user_id);
    }
}
