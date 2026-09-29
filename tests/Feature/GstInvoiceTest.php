<?php

namespace Tests\Feature;

use App\Core\Registry\AddonRegistry;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\InvoiceGst\GstInvoiceAddon;
use Packages\InvoiceGst\Services\GstInvoiceService;
use Tests\TestCase;

class GstInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $customerUser;

    protected TaxClass $gst18;

    protected TaxClass $gst28;

    protected ProductVariant $cementVariant;

    protected ProductVariant $steelVariant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Users
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        $this->adminUser = User::factory()->create([
            'name' => 'Finance Controller',
            'email' => 'finance@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->customerUser = User::factory()->create([
            'name' => 'Rajesh Contractor',
            'email' => 'rajesh@example.test',
            'phone' => '9876543210',
        ]);

        // 2. Catalog & Taxes
        $this->gst18 = TaxClass::create(['name' => 'GST 18%', 'rate_percentage' => 18.00]);
        $this->gst28 = TaxClass::create(['name' => 'GST 28%', 'rate_percentage' => 28.00]);

        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech', 'is_active' => true]);
        $cat = Category::create(['name' => 'Structural Materials', 'slug' => 'structural', 'is_active' => true]);

        $cementProduct = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'UltraTech Super Cement 53',
            'slug' => 'ultratech-super-cement-53',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->cementVariant = ProductVariant::create([
            'product_id' => $cementProduct->id,
            'tax_class_id' => $this->gst18->id,
            'sku' => 'CEM-53-BAG-50KG',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => 1,
            'mrp' => 45000,
            'selling_price' => 40000, // ₹400
            'status' => 'active',
        ]);

        $steelProduct = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'Tata Tiscon Fe550D TMT Steel Rebar',
            'slug' => 'tata-tiscon-tmt-steel',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->steelVariant = ProductVariant::create([
            'product_id' => $steelProduct->id,
            'tax_class_id' => $this->gst18->id,
            'sku' => 'STEEL-TMT-12MM-12M',
            'name' => '12mm Bar',
            'unit' => 'bar',
            'pack_size' => 1,
            'mrp' => 85000,
            'selling_price' => 75000, // ₹750
            'status' => 'active',
        ]);
    }

    protected function createTestOrder(array $shipping = [], array $items = []): Order
    {
        $defaultShipping = [
            'recipient_name' => 'Rajesh Contractor',
            'phone' => '+91 9876543210',
            'address_line_1' => 'Site #42, Electronic City Phase 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
        ];

        $order = Order::create([
            'order_number' => 'ORD-2026-TAX-'.rand(1000, 9999),
            'user_id' => $this->customerUser->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 400000,
            'grand_total' => 472000, // 400000 + 18% GST (72000)
            'billing_address_snapshot' => array_merge($defaultShipping, $shipping),
            'shipping_address_snapshot' => array_merge($defaultShipping, $shipping),
            'placed_at' => now(),
        ]);

        if (empty($items)) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $this->cementVariant->id,
                'sku_snapshot' => $this->cementVariant->sku,
                'product_name_snapshot' => 'UltraTech Super Cement 53',
                'variant_name_snapshot' => '50kg Bag',
                'unit_price' => 40000,
                'quantity' => 10,
                'discount' => 0,
                'tax' => 72000,
                'line_total' => 472000,
            ]);
        } else {
            foreach ($items as $item) {
                OrderItem::create(array_merge(['order_id' => $order->id], $item));
            }
        }

        return $order->fresh(['items.variant.taxClass', 'user']);
    }

    public function test_gst_invoice_addon_registered_in_addon_registry(): void
    {
        $registry = app(AddonRegistry::class);

        $this->assertTrue($registry->has('invoice-gst'));
        $addon = $registry->get('invoice-gst');

        $this->assertInstanceOf(GstInvoiceAddon::class, $addon);
        $this->assertTrue($addon->isEnabled());
        $this->assertEquals('1.0.0', $addon->version());
        $this->assertEquals('GST Tax Invoice & E-Invoicing Engine', $addon->name());
    }

    public function test_intra_state_order_calculates_cgst_and_sgst_split(): void
    {
        // Destination in Karnataka (State Code 29, same as seller)
        $order = $this->createTestOrder([
            'state' => 'Karnataka',
        ]);

        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $this->assertEquals('INTRA_STATE', $invoice->supply_type);
        $this->assertEquals('29', $invoice->place_of_supply_state_code);

        // 10 bags * ₹400 = ₹4,000 (400000 paise) taxable value
        $this->assertEquals(400000, $invoice->taxable_amount);

        // 18% GST split into 9% CGST (36000 paise) and 9% SGST (36000 paise)
        $this->assertEquals(36000, $invoice->cgst_amount);
        $this->assertEquals(36000, $invoice->sgst_amount);
        $this->assertEquals(0, $invoice->igst_amount);

        $this->assertTrue($invoice->isIntraState());
        $this->assertFalse($invoice->isInterState());

        $firstItem = $invoice->items->first();
        $this->assertEquals(9.00, $firstItem->cgst_rate);
        $this->assertEquals(9.00, $firstItem->sgst_rate);
        $this->assertEquals(36000, $firstItem->cgst_amount);
        $this->assertEquals(36000, $firstItem->sgst_amount);
        $this->assertEquals(0, $firstItem->igst_amount);
    }

    public function test_inter_state_order_calculates_full_igst(): void
    {
        // Destination in Maharashtra (State Code 27, different from seller Karnataka 29)
        $order = $this->createTestOrder([
            'state' => 'Maharashtra',
            'city' => 'Pune',
            'pincode' => '411001',
        ]);

        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $this->assertEquals('INTER_STATE', $invoice->supply_type);
        $this->assertEquals('27', $invoice->place_of_supply_state_code);

        $this->assertEquals(400000, $invoice->taxable_amount);
        $this->assertEquals(0, $invoice->cgst_amount);
        $this->assertEquals(0, $invoice->sgst_amount);
        // Full 18% IGST = 72000 paise
        $this->assertEquals(72000, $invoice->igst_amount);

        $this->assertFalse($invoice->isIntraState());
        $this->assertTrue($invoice->isInterState());

        $firstItem = $invoice->items->first();
        $this->assertEquals(18.00, $firstItem->igst_rate);
        $this->assertEquals(72000, $firstItem->igst_amount);
        $this->assertEquals(0, $firstItem->cgst_amount);
    }

    public function test_b2b_order_generates_invoice_with_company_gstin_and_legal_name(): void
    {
        $company = Company::create([
            'name' => 'Apex Infra Projects Pvt Ltd',
            'company_code' => 'APEX-INFRA',
            'tax_id' => '29ABCDE1234F1Z5',
            'status' => 'active',
            'credit_limit' => 5000000,
            'available_credit' => 5000000,
        ]);

        CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $this->customerUser->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $order = $this->createTestOrder();

        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $this->assertTrue($invoice->is_b2b);
        $this->assertEquals('Apex Infra Projects Pvt Ltd', $invoice->buyer_name);
        $this->assertEquals('29ABCDE1234F1Z5', $invoice->buyer_gstin);
    }

    public function test_b2c_order_generates_consumer_invoice_without_buyer_gstin(): void
    {
        $order = $this->createTestOrder();

        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $this->assertFalse($invoice->is_b2b);
        $this->assertNull($invoice->buyer_gstin);
        $this->assertEquals('Rajesh Contractor', $invoice->buyer_name);
    }

    public function test_sequential_invoice_number_generation_with_financial_year(): void
    {
        $service = app(GstInvoiceService::class);

        $order1 = $this->createTestOrder();
        $invoice1 = $service->generateForOrder($order1);

        $order2 = $this->createTestOrder();
        $invoice2 = $service->generateForOrder($order2);

        $fy = $invoice1->financial_year;
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}$/', $fy);

        $this->assertEquals("INV/{$fy}/0001", $invoice1->invoice_number);
        $this->assertEquals("INV/{$fy}/0002", $invoice2->invoice_number);
        $this->assertEquals(1, $invoice1->sequence_number);
        $this->assertEquals(2, $invoice2->sequence_number);
    }

    public function test_hsn_summary_aggregates_taxable_value_and_tax_amounts(): void
    {
        // Order with 2 line items: Cement (HSN 2523) and Steel (HSN 7214)
        $order = $this->createTestOrder([], [
            [
                'product_variant_id' => $this->cementVariant->id,
                'sku_snapshot' => $this->cementVariant->sku,
                'product_name_snapshot' => 'UltraTech Super Cement 53',
                'variant_name_snapshot' => '50kg Bag',
                'unit_price' => 40000,
                'quantity' => 10, // 400000 paise
                'discount' => 0,
                'tax' => 72000,
                'line_total' => 472000,
            ],
            [
                'product_variant_id' => $this->steelVariant->id,
                'sku_snapshot' => $this->steelVariant->sku,
                'product_name_snapshot' => 'Tata Tiscon Fe550D TMT Steel',
                'variant_name_snapshot' => '12mm Bar',
                'unit_price' => 75000,
                'quantity' => 4, // 300000 paise
                'discount' => 0,
                'tax' => 54000,
                'line_total' => 354000,
            ],
        ]);

        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $summary = $invoice->hsnSummary();

        // Two distinct HSN groups: 2523 and 7214
        $this->assertCount(2, $summary);

        $hsnCodes = array_column($summary, 'hsn_code');
        $this->assertContains('2523', $hsnCodes);
        $this->assertContains('7214', $hsnCodes);

        $cementSummary = collect($summary)->firstWhere('hsn_code', '2523');
        $this->assertEquals(400000, $cementSummary['taxable_value']);
        $this->assertEquals(72000, $cementSummary['total_tax']);

        $steelSummary = collect($summary)->firstWhere('hsn_code', '7214');
        $this->assertEquals(300000, $steelSummary['taxable_value']);
        $this->assertEquals(54000, $steelSummary['total_tax']);
    }

    public function test_customer_can_view_own_tax_invoice(): void
    {
        $order = $this->createTestOrder();
        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $response = $this->actingAs($this->customerUser)->get(route('account.invoices.show', $invoice->invoice_number));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('TAX INVOICE');
        $response->assertSee($invoice->seller_gstin);
        $response->assertSee('UltraTech Super Cement 53');
    }

    public function test_unauthorized_user_cannot_view_others_invoice(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'intruder@example.test',
        ]);

        $order = $this->createTestOrder();
        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $response = $this->actingAs($otherUser)->get(route('account.invoices.show', $invoice->invoice_number));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_invoice_register_and_cancel_invoice(): void
    {
        $order = $this->createTestOrder();
        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        // 1. Admin views financial register
        $indexResponse = $this->actingAs($this->adminUser)->get(route('admin.invoices.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($invoice->invoice_number);

        // 2. Admin cancels invoice with credit note reason
        $cancelResponse = $this->actingAs($this->adminUser)->post(route('admin.invoices.cancel', $invoice->id), [
            'reason' => 'Customer requested order cancellation prior to dispatch',
        ]);

        $cancelResponse->assertRedirect();
        $this->assertEquals('cancelled', $invoice->fresh()->status);
        $this->assertTrue($invoice->fresh()->isCancelled());
        $this->assertEquals('Customer requested order cancellation prior to dispatch', $invoice->fresh()->cancellation_reason);
    }

    public function test_staff_with_order_manager_role_can_view_tax_invoice(): void
    {
        $staffRole = Role::firstOrCreate(['slug' => 'order-manager'], ['name' => 'Order Manager']);
        $staffUser = User::factory()->create([
            'email' => 'manager@universal-ecom.test',
        ]);
        $staffUser->roles()->attach($staffRole);

        $order = $this->createTestOrder();
        $service = app(GstInvoiceService::class);
        $invoice = $service->generateForOrder($order);

        $response = $this->actingAs($staffUser)->get(route('account.invoices.show', $invoice->invoice_number));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('TAX INVOICE');
    }

    public function test_viewing_invoice_via_basic_invoice_number_resolves_and_generates_gst_invoice(): void
    {
        $order = $this->createTestOrder();
        $basicInvoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-20260928-TEST99',
            'amount' => $order->grand_total,
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('account.invoices.show', $basicInvoice->invoice_number));

        $response->assertOk();
        $response->assertSee('TAX INVOICE');
        $this->assertDatabaseHas('gst_invoices', [
            'order_id' => $order->id,
        ]);
    }
}
