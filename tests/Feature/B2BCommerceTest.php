<?php

namespace Tests\Feature;

use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Core\Registry\PricingAdjustmentRegistry;
use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Pricing\PricingPipeline;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\B2BCommerce\B2BCommerceAddon;
use Packages\B2BCommerce\Gateways\PurchaseOrderGateway;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\ContractPriceList;
use Packages\B2BCommerce\Models\ContractVariantPrice;
use Packages\B2BCommerce\Models\CustomerGroup;
use Packages\B2BCommerce\Models\PurchaseOrder;
use Packages\B2BCommerce\Services\B2BApprovalService;
use Packages\B2BCommerce\Services\B2BContractPricingAdjuster;
use Packages\B2BCommerce\Services\CompanyService;
use Packages\DomainConstruction\Seeders\ConstructionDomainSeeder;
use Packages\PromotionEngine\Models\Promotion;
use RuntimeException;
use Tests\TestCase;

class B2BCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConstructionDomainSeeder::class);
    }

    public function test_b2b_addon_registers_in_addon_registry_and_payment_pricing_registries(): void
    {
        $addonRegistry = app(AddonRegistry::class);
        $this->assertTrue($addonRegistry->has('b2b-commerce'));

        $addon = $addonRegistry->get('b2b-commerce');
        $this->assertInstanceOf(B2BCommerceAddon::class, $addon);
        $this->assertTrue($addon->isEnabled());
        $this->assertEquals('1.0.0', $addon->version());

        $gatewayRegistry = app(PaymentGatewayRegistry::class);
        $this->assertTrue($gatewayRegistry->has('purchase_order'));
        $this->assertInstanceOf(PurchaseOrderGateway::class, $gatewayRegistry->get('purchase_order'));

        $pricingRegistry = app(PricingAdjustmentRegistry::class);
        $this->assertTrue($pricingRegistry->has('b2b-contract-pricing'));
        $this->assertInstanceOf(B2BContractPricingAdjuster::class, $pricingRegistry->get('b2b-contract-pricing'));
    }

    public function test_company_creation_and_user_roles_with_spending_limits(): void
    {
        $companyService = app(CompanyService::class);

        $company = $companyService->createCompany([
            'name' => 'BuildCorp Construction Ltd.',
            'company_code' => 'BUILDCORP',
            'tax_id' => '29ABCDE1234F1Z5',
            'status' => 'active',
            'credit_limit' => 5000000, // ₹50,000
            'credit_balance' => 5000000,
            'payment_terms_days' => 30,
        ]);

        $adminUser = User::factory()->create(['name' => 'Corp Admin']);
        $approverUser = User::factory()->create(['name' => 'Site Supervisor']);
        $buyerUser = User::factory()->create(['name' => 'Procurement Officer']);

        $cuAdmin = $companyService->attachUser($company, $adminUser, 'admin');
        $cuApprover = $companyService->attachUser($company, $approverUser, 'approver');
        $cuBuyer = $companyService->attachUser($company, $buyerUser, 'buyer', 200000); // ₹2,000 spending limit

        $this->assertTrue($cuAdmin->isAdmin());
        $this->assertFalse($cuAdmin->requiresApproval(10000000));

        $this->assertTrue($cuApprover->isApprover());
        $this->assertFalse($cuApprover->requiresApproval(10000000));

        $this->assertTrue($cuBuyer->isBuyer());
        $this->assertFalse($cuBuyer->requiresApproval(150000)); // ₹1,500 <= ₹2,000 limit
        $this->assertTrue($cuBuyer->requiresApproval(250000));  // ₹2,500 > ₹2,000 limit

        // Test dynamic Eloquent relations resolved on User
        $this->assertInstanceOf(CompanyUser::class, $buyerUser->companyUser);
        $this->assertInstanceOf(Company::class, $buyerUser->company);
        $this->assertEquals($company->id, $buyerUser->company->id);
    }

    public function test_negotiated_contract_price_list_overrides_catalog_price_in_cart(): void
    {
        $companyService = app(CompanyService::class);
        $company = $companyService->createCompany([
            'name' => 'InfraMax Group',
            'company_code' => 'INFRAMAX',
            'credit_limit' => 10000000,
            'credit_balance' => 10000000,
        ]);

        $buyer = User::factory()->create();
        $companyService->attachUser($company, $buyer, 'buyer');

        // Variant from seeder (e.g. UltraTech Cement 50kg)
        $variant = ProductVariant::firstOrFail();
        $originalPrice = $variant->selling_price; // minor units

        // Pause retail promotions to isolate contract pricing calculation
        Promotion::query()->update(['status' => 'paused']);

        // Negotiated price: lower than selling price
        $contractPrice = $originalPrice - 5000;
        $priceList = ContractPriceList::create([
            'company_id' => $company->id,
            'name' => 'InfraMax Negotiated Cement Contract',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        ContractVariantPrice::create([
            'price_list_id' => $priceList->id,
            'product_variant_id' => $variant->id,
            'custom_price' => $contractPrice,
            'min_quantity' => 1,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($buyer, 'test-b2b-cart');
        $cartService->addItem($cart, $variant->id, 2);

        $pricingPipeline = app(PricingPipeline::class);
        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();

        $totals = $pricingPipeline->calculate($cart->refresh(), $address);

        $expectedDiscount = ($originalPrice - $contractPrice) * 2;
        $this->assertEquals($expectedDiscount, $totals['discount_total']);
        $this->assertNotEmpty($totals['applied_promotions']);
        $this->assertContains('b2b_contract_pricing', array_column($totals['applied_promotions'], 'type'));
    }

    public function test_customer_group_percentage_discount_applies_when_no_variant_contract_price(): void
    {
        $group = CustomerGroup::create([
            'name' => 'Tier 1 Contractors',
            'code' => 'tier1',
            'discount_percentage' => 15.00, // 15% discount
        ]);

        $company = Company::create([
            'name' => 'Apex Builders',
            'company_code' => 'APEX',
            'customer_group_id' => $group->id,
            'status' => 'active',
            'credit_limit' => 10000000,
            'credit_balance' => 10000000,
        ]);

        $buyer = User::factory()->create();
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer');

        // Pause retail promotions to isolate customer group calculation
        Promotion::query()->update(['status' => 'paused']);

        $variant = ProductVariant::firstOrFail();
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($buyer, 'test-group-cart');
        $cartService->addItem($cart, $variant->id, 2);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $totals = app(PricingPipeline::class)->calculate($cart->refresh(), $address);

        $rawSubtotal = $variant->selling_price * 2;
        $expectedDiscount = (int) round($rawSubtotal * 0.15);

        $this->assertEquals($expectedDiscount, $totals['discount_total']);
        $this->assertContains('b2b_group_discount', array_column($totals['applied_promotions'], 'type'));
    }

    public function test_purchase_order_checkout_within_spending_limit_confirms_and_deducts_credit(): void
    {
        $companyService = app(CompanyService::class);
        $initialCredit = 5000000; // ₹50,000

        $company = $companyService->createCompany([
            'name' => 'MegaStruct Infra',
            'company_code' => 'MEGASTRUCT',
            'credit_limit' => $initialCredit,
            'credit_balance' => $initialCredit,
            'payment_terms_days' => 45,
        ]);

        $buyer = User::factory()->create();
        $companyService->attachUser($company, $buyer, 'buyer', 1000000); // ₹10,000 limit

        $variant = ProductVariant::firstOrFail();
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($buyer, 'test-po-cart-1');
        $cartService->addItem($cart, $variant->id, 1);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $checkoutService = app(CheckoutService::class);

        $order = $checkoutService->checkout(
            $cart,
            $address,
            $address,
            'purchase_order',
            'PO Order Notes',
            $buyer,
            ['po_number' => 'PO-MEGA-2026-001']
        );

        // Within buyer spending limit and credit line => auto-confirmed
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals('pending', $order->payment_status);

        // PO record created with approved status
        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('approved', $po->status);
        $this->assertEquals('PO-MEGA-2026-001', $po->po_number);
        $this->assertEquals(45, $po->payment_terms_days);

        // Credit balance deducted
        $company->refresh();
        $this->assertEquals($initialCredit - $order->grand_total, $company->credit_balance);

        // Stock reservations consumed
        $activeReservations = StockReservation::where('order_id', $order->id)->where('status', 'active')->count();
        $this->assertEquals(0, $activeReservations);
    }

    public function test_purchase_order_checkout_fails_when_credit_limit_exceeded(): void
    {
        $companyService = app(CompanyService::class);
        $tinyCredit = 1000; // ₹10.00 credit

        $company = $companyService->createCompany([
            'name' => 'Underfunded Contracting',
            'company_code' => 'UNDERFUND',
            'credit_limit' => $tinyCredit,
            'credit_balance' => $tinyCredit,
        ]);

        $buyer = User::factory()->create();
        $companyService->attachUser($company, $buyer, 'buyer');

        $variant = ProductVariant::firstOrFail();
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($buyer, 'test-tiny-credit');
        $cartService->addItem($cart, $variant->id, 1);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $checkoutService = app(CheckoutService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Company credit limit exceeded');

        $checkoutService->checkout($cart, $address, $address, 'purchase_order', null, $buyer);
    }

    public function test_buyer_order_exceeding_spending_limit_enters_pending_approval(): void
    {
        $companyService = app(CompanyService::class);
        $company = $companyService->createCompany([
            'name' => 'HighValue Builders',
            'company_code' => 'HVBUILD',
            'credit_limit' => 10000000, // ₹100,000
            'credit_balance' => 10000000,
        ]);

        $buyer = User::factory()->create();
        $companyService->attachUser($company, $buyer, 'buyer', 10000); // Tiny spending limit: ₹100

        $variant = ProductVariant::firstOrFail(); // Price is > ₹100
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($buyer, 'test-spending-limit-cart');
        $cartService->addItem($cart, $variant->id, 2);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $checkoutService = app(CheckoutService::class);

        $order = $checkoutService->checkout($cart, $address, $address, 'purchase_order', null, $buyer, ['po_number' => 'PO-EXCEED-01']);

        // Order and PO must enter pending_approval
        $this->assertEquals('pending_approval', $order->status);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('pending_approval', $po->status);
        $this->assertTrue($po->isPendingApproval());

        // Credit balance reserved
        $this->assertEquals(10000000 - $order->grand_total, $company->refresh()->credit_balance);

        // Stock reservations remain active (held) awaiting supervisor sign-off
        $activeReservations = StockReservation::where('order_id', $order->id)->where('status', 'active')->count();
        $this->assertGreaterThan(0, $activeReservations);
    }

    public function test_supervisor_approves_purchase_order_and_confirms_order_and_consumes_stock(): void
    {
        $companyService = app(CompanyService::class);
        $company = $companyService->createCompany([
            'name' => 'Approval Corp',
            'company_code' => 'APPROVCORP',
            'credit_limit' => 10000000,
            'credit_balance' => 10000000,
        ]);

        $buyer = User::factory()->create(['name' => 'Junior Buyer']);
        $supervisor = User::factory()->create(['name' => 'Senior Approver']);

        $companyService->attachUser($company, $buyer, 'buyer', 10000); // ₹100 limit
        $companyService->attachUser($company, $supervisor, 'approver');

        $variant = ProductVariant::firstOrFail();
        $cart = app(CartService::class)->getOrCreateCart($buyer, 'test-approve-cart');
        app(CartService::class)->addItem($cart, $variant->id, 1);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'purchase_order', null, $buyer);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('pending_approval', $po->status);

        // Supervisor approves the PO
        $approvalService = app(B2BApprovalService::class);
        $updatedPo = $approvalService->approvePurchaseOrder($po, $supervisor, 'Site requirements verified.');

        $this->assertEquals('approved', $updatedPo->status);
        $this->assertEquals($supervisor->id, $updatedPo->approver_user_id);
        $this->assertNotNull($updatedPo->approved_at);

        // Order transitions to confirmed
        $this->assertEquals('confirmed', $order->refresh()->status);

        // Order status history created
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'new_status' => 'confirmed',
            'user_id' => $supervisor->id,
        ]);

        // Stock reservations consumed
        $activeReservations = StockReservation::where('order_id', $order->id)->where('status', 'active')->count();
        $this->assertEquals(0, $activeReservations);
    }

    public function test_supervisor_rejects_purchase_order_restores_credit_and_releases_stock(): void
    {
        $companyService = app(CompanyService::class);
        $initialCredit = 10000000;
        $company = $companyService->createCompany([
            'name' => 'Reject Corp',
            'company_code' => 'REJECTCORP',
            'credit_limit' => $initialCredit,
            'credit_balance' => $initialCredit,
        ]);

        $buyer = User::factory()->create(['name' => 'Junior Buyer 2']);
        $supervisor = User::factory()->create(['name' => 'Strict Supervisor']);

        $companyService->attachUser($company, $buyer, 'buyer', 10000); // ₹100 limit
        $companyService->attachUser($company, $supervisor, 'approver');

        $variant = ProductVariant::firstOrFail();
        $cart = app(CartService::class)->getOrCreateCart($buyer, 'test-reject-cart');
        app(CartService::class)->addItem($cart, $variant->id, 1);

        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'purchase_order', null, $buyer);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $orderTotal = $order->grand_total;
        $this->assertEquals($initialCredit - $orderTotal, $company->refresh()->credit_balance);

        // Supervisor rejects the PO
        $approvalService = app(B2BApprovalService::class);
        $updatedPo = $approvalService->rejectPurchaseOrder($po, $supervisor, 'Duplicate requisition.');

        $this->assertEquals('rejected', $updatedPo->status);
        $this->assertEquals('cancelled', $order->refresh()->status);

        // Order status history created
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'new_status' => 'cancelled',
            'user_id' => $supervisor->id,
        ]);

        // Company credit balance restored
        $this->assertEquals($initialCredit, $company->refresh()->credit_balance);

        // Stock reservations released
        $releasedReservations = StockReservation::where('order_id', $order->id)->where('status', 'released')->count();
        $this->assertGreaterThan(0, $releasedReservations);
    }

    public function test_admin_b2b_companies_and_purchase_orders_management_web_endpoints(): void
    {
        $admin = User::where('email', 'admin@ecom-laravel.test')->firstOrFail();

        // 1. Companies index
        $response = $this->actingAs($admin)->get('/admin/b2b/companies');
        $response->assertStatus(200);
        $response->assertSee('Corporate Organizations');

        // 2. Register company via admin
        $createResponse = $this->actingAs($admin)->post('/admin/b2b/companies', [
            'name' => 'Larsen & Infra Partners',
            'company_code' => 'LARSEN-INFRA',
            'credit_limit_in_rupees' => 250000,
            'payment_terms_days' => 60,
        ]);
        $company = Company::where('company_code', 'LARSEN-INFRA')->firstOrFail();
        $createResponse->assertRedirect(route('admin.b2b.companies.show', $company->id));
        $this->assertEquals(25000000, $company->credit_limit);

        // 3. View company profile
        $showResponse = $this->actingAs($admin)->get("/admin/b2b/companies/{$company->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Larsen & Infra Partners');

        // 4. Update credit limit
        $limitResponse = $this->actingAs($admin)->post("/admin/b2b/companies/{$company->id}/credit-limit", [
            'credit_limit_in_rupees' => 500000,
        ]);
        $limitResponse->assertSessionHas('success');
        $this->assertEquals(50000000, $company->refresh()->credit_limit);

        // 5. Toggle company status
        $statusResponse = $this->actingAs($admin)->post("/admin/b2b/companies/{$company->id}/status", [
            'status' => 'suspended',
        ]);
        $statusResponse->assertSessionHas('success');
        $this->assertEquals('suspended', $company->refresh()->status);

        // 6. Contract price lists view
        $priceListResponse = $this->actingAs($admin)->get('/admin/b2b/price-lists');
        $priceListResponse->assertStatus(200);

        // 7. Purchase orders view
        $poResponse = $this->actingAs($admin)->get('/admin/b2b/purchase-orders');
        $poResponse->assertStatus(200);
        $poResponse->assertSee('All Orders');
        $poResponse->assertSee('Pending Approval');

        // 8. Admin web approval of a pending PO
        $buyer = User::factory()->create();
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer', 100);
        $company->update(['status' => 'active']);

        $variant = ProductVariant::firstOrFail();
        $cart = app(CartService::class)->getOrCreateCart($buyer, 'admin-po-test-cart');
        app(CartService::class)->addItem($cart, $variant->id, 1);
        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'purchase_order', null, $buyer);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('pending_approval', $po->status);

        $approveResponse = $this->actingAs($admin)->post("/admin/b2b/purchase-orders/{$po->id}/approve", [
            'notes' => 'Central dispatcher sign-off',
        ]);
        $approveResponse->assertSessionHas('success');
        $this->assertEquals('approved', $po->fresh()->status);
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    public function test_customer_account_b2b_portal_renders_company_credit_and_requisitions(): void
    {
        $company = Company::create([
            'name' => 'Apex Infra Projects Ltd',
            'company_code' => 'APEX-INFRA',
            'tax_id' => '29ABCDE1234F1Z5',
            'status' => 'active',
            'credit_limit' => 10000000, // ₹100,000
            'credit_balance' => 8500000, // ₹85,000
            'payment_terms_days' => 45,
        ]);

        $buyer = User::factory()->create(['name' => 'Suresh Buyer']);
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer', 500000); // ₹5,000 spending limit

        $response = $this->actingAs($buyer)->get(route('account.b2b.index'));

        $response->assertOk();
        $response->assertSee('Apex Infra Projects Ltd');
        $response->assertSee('APEX-INFRA');
        $response->assertSee('29ABCDE1234F1Z5');
        $response->assertSee('₹100,000.00'); // Credit limit
        $response->assertSee('₹85,000.00');  // Available balance
        $response->assertSee('Net 45 Days');
        $response->assertSee('₹5,000.00');   // Personal spending limit
    }

    public function test_non_b2b_user_redirected_away_from_b2b_portal(): void
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('account.b2b.index'));

        $response->assertRedirect(route('account.orders'));
        $response->assertSessionHas('error');
    }

    public function test_company_supervisor_can_approve_pending_requisition_from_b2b_portal(): void
    {
        $company = Company::create([
            'name' => 'Metro Highrise Corp',
            'company_code' => 'METRO-HIGH',
            'status' => 'active',
            'credit_limit' => 20000000,
            'credit_balance' => 15000000,
            'payment_terms_days' => 30,
        ]);

        $supervisor = User::factory()->create(['name' => 'Rajesh Supervisor']);
        $buyer = User::factory()->create(['name' => 'Amit Buyer']);

        app(CompanyService::class)->attachUser($company, $supervisor, 'approver');
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer', 1000); // Low spending limit

        $variant = ProductVariant::firstOrFail();
        $cart = app(CartService::class)->getOrCreateCart($buyer, 'po-approval-test-cart');
        app(CartService::class)->addItem($cart, $variant->id, 1);
        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'purchase_order', null, $buyer);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('pending_approval', $po->status);
        $this->assertEquals('pending_approval', $order->status);

        // Supervisor views portal and sees pending requisition
        $viewResponse = $this->actingAs($supervisor)->get(route('account.b2b.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Pending Purchase Orders Awaiting Your Sign-Off');
        $viewResponse->assertSee($po->po_number);

        // Supervisor approves
        $approveResponse = $this->actingAs($supervisor)->post(route('account.b2b.approve', $po->id), [
            'notes' => 'Approved for Phase 2 construction work.',
        ]);
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        $this->assertEquals('approved', $po->fresh()->status);
        $this->assertEquals($supervisor->id, $po->fresh()->approver_user_id);
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    public function test_company_supervisor_can_reject_pending_requisition_and_restores_credit(): void
    {
        $company = Company::create([
            'name' => 'Zenith Towers Ltd',
            'company_code' => 'ZENITH-TOWERS',
            'status' => 'active',
            'credit_limit' => 10000000,
            'credit_balance' => 10000000,
            'payment_terms_days' => 30,
        ]);

        $supervisor = User::factory()->create(['name' => 'Sunil Director']);
        $buyer = User::factory()->create(['name' => 'Vikram Buyer']);

        app(CompanyService::class)->attachUser($company, $supervisor, 'admin');
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer', 100);

        $variant = ProductVariant::firstOrFail();
        $cart = app(CartService::class)->getOrCreateCart($buyer, 'po-reject-test-cart');
        app(CartService::class)->addItem($cart, $variant->id, 1);
        $address = Address::where('user_id', User::where('email', 'contractor@acmebuild.test')->first()->id)->firstOrFail();
        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'purchase_order', null, $buyer);

        $po = PurchaseOrder::where('order_id', $order->id)->firstOrFail();
        $initialBalanceAfterOrder = $company->fresh()->credit_balance;

        // Supervisor rejects
        $rejectResponse = $this->actingAs($supervisor)->post(route('account.b2b.reject', $po->id), [
            'notes' => 'Budget allocated to Q4.',
        ]);
        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHas('success');

        $this->assertEquals('rejected', $po->fresh()->status);
        $this->assertEquals(10000000, $company->fresh()->credit_balance); // Restored
    }

    public function test_unauthorized_buyer_cannot_approve_requisition(): void
    {
        $company = Company::create([
            'name' => 'Delta Builders Ltd',
            'company_code' => 'DELTA-BLD',
            'status' => 'active',
            'credit_limit' => 5000000,
            'credit_balance' => 5000000,
        ]);

        $buyer = User::factory()->create();
        app(CompanyService::class)->attachUser($company, $buyer, 'buyer', 100);

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-TEST-FORBIDDEN',
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 50000,
            'discount_total' => 0,
            'tax_total' => 0,
            'delivery_fee' => 0,
            'grand_total' => 50000,
            'billing_address_snapshot' => ['recipient_name' => 'Buyer', 'city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['recipient_name' => 'Buyer', 'city' => 'Bengaluru'],
            'placed_at' => now(),
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'po_number' => 'PO-TEST-FORBIDDEN',
            'status' => 'pending_approval',
            'amount' => 50000,
            'requester_user_id' => $buyer->id,
        ]);

        $response = $this->actingAs($buyer)->post(route('account.b2b.approve', $po->id));
        $response->assertStatus(403);
    }

    public function test_printable_purchase_order_document_view(): void
    {
        $company = Company::create([
            'name' => 'Sterling Infrastructure Pvt Ltd',
            'company_code' => 'STERLING-INFRA',
            'tax_id' => '29XYZAB1234C1Z9',
            'status' => 'active',
            'credit_limit' => 10000000,
            'credit_balance' => 10000000,
            'payment_terms_days' => 30,
        ]);

        $member = User::factory()->create();
        app(CompanyService::class)->attachUser($company, $member, 'approver');

        $order = Order::create([
            'user_id' => $member->id,
            'order_number' => 'ORD-DOC-2026-001',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 750000,
            'discount_total' => 0,
            'tax_total' => 0,
            'delivery_fee' => 0,
            'grand_total' => 750000,
            'billing_address_snapshot' => ['recipient_name' => 'Sterling Site Alpha', 'city' => 'Bengaluru', 'address_line1' => 'Plot 44, Tech Park'],
            'shipping_address_snapshot' => ['recipient_name' => 'Sterling Site Alpha', 'city' => 'Bengaluru', 'address_line1' => 'Plot 44, Tech Park'],
            'placed_at' => now(),
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'po_number' => 'PO-DOC-2026-001',
            'status' => 'approved',
            'amount' => 750000,
            'payment_terms_days' => 30,
            'due_date' => now()->addDays(30),
            'requester_user_id' => $member->id,
            'approver_user_id' => $member->id,
            'approved_at' => now(),
            'approval_notes' => 'Authorized under credit line.',
        ]);

        $response = $this->actingAs($member)->get(route('account.b2b.po.show', $po->po_number));

        $response->assertOk();
        $response->assertSee('Commercial Purchase Order');
        $response->assertSee('PO-DOC-2026-001');
        $response->assertSee('Sterling Infrastructure Pvt Ltd');
        $response->assertSee('29XYZAB1234C1Z9');
        $response->assertSee('Net 30 Days');
        $response->assertSee('Authorized by');
    }
}
