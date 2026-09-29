<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Security hardening test suite covering:
 * - IDOR (Insecure Direct Object Reference) isolation
 * - Mass-assignment guard on server-authoritative pricing
 * - Audit trail recording for sensitive actions
 * - Admin route protection
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected Warehouse $warehouse;

    protected TaxClass $taxClass;

    protected Brand $brand;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->userA = User::factory()->create(['email' => 'alice@security-test.test']);
        $this->userB = User::factory()->create(['email' => 'bob@security-test.test']);

        $this->warehouse = Warehouse::create([
            'code' => 'SEC-WH',
            'name' => 'Security Test Warehouse',
            'is_active' => true,
        ]);

        $this->taxClass = TaxClass::create([
            'name' => 'GST 18%',
            'code' => 'GST_18_SEC',
            'rate' => 1800,
        ]);

        $this->brand = Brand::create(['name' => 'SecBrand', 'slug' => 'sec-brand', 'is_active' => true]);
        $this->category = Category::create(['name' => 'SecCategory', 'slug' => 'sec-cat', 'is_active' => true]);
    }

    // -------------------------------------------------------------------------
    // 1. IDOR — Order isolation
    // -------------------------------------------------------------------------

    /**
     * Customer A must not be able to read Customer B's order via the API.
     */
    public function test_01_idor_customer_cannot_read_another_users_order(): void
    {
        $orderB = Order::create([
            'order_number' => 'ORD-B-SEC-001',
            'user_id' => $this->userB->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'tax_total' => 1800,
            'grand_total' => 11800,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson("/api/v1/orders/{$orderB->order_number}");

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // 2. IDOR — Order cancel isolation
    // -------------------------------------------------------------------------

    /**
     * Customer A must not be able to cancel Customer B's order.
     */
    public function test_02_idor_customer_cannot_cancel_another_users_order(): void
    {
        $orderB = Order::create([
            'order_number' => 'ORD-B-SEC-002',
            'user_id' => $this->userB->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 5000,
            'tax_total' => 900,
            'grand_total' => 5900,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->postJson("/api/v1/orders/{$orderB->order_number}/cancel");

        // Must be 404 (not found for this user), never 200
        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // 3. Server-authoritative pricing — mass-assignment guard
    // -------------------------------------------------------------------------

    /**
     * Even if a client submits a tampered unit_price or price, the server must
     * store the authoritative selling_price from the product variant.
     */
    public function test_03_server_enforces_authoritative_price_on_cart_add(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Security Price Guard Product',
            'slug' => 'sec-price-guard',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'SEC-PRICE-001',
            'name' => '1kg Pack',
            'mrp' => 50000,
            'selling_price' => 40000, // authoritative price
            'status' => 'active',
        ]);

        InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 100,
            'reserved' => 0,
            'available' => 100,
        ]);

        Sanctum::actingAs($this->userA);

        // Malicious client attempts to submit price=1 (cheapest possible)
        $response = $this->withHeader('X-Session-Token', 'sec-price-test-token')
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 1,
                'unit_price' => 1,
                'total_price' => 1,
            ]);

        $response->assertOk();

        $cartItem = CartItem::where('product_variant_id', $variant->id)->firstOrFail();

        $this->assertEquals(
            40000,
            $cartItem->unit_price,
            'Server must store the authoritative selling_price (40000), not the client-submitted price (1)'
        );
    }

    // -------------------------------------------------------------------------
    // 4. RBAC — customer cannot access admin refund endpoint
    // -------------------------------------------------------------------------

    /**
     * A regular customer must be forbidden (403) from issuing a refund
     * even when authenticated, because they lack the refunds.create permission.
     */
    public function test_04_customer_cannot_issue_refund_on_admin_endpoint(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-RBAC-SEC-004',
            'user_id' => $this->userA->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'tax_total' => 1800,
            'grand_total' => 11800,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        // userA is a plain customer with no special permissions
        Sanctum::actingAs($this->userA);

        $response = $this->postJson("/api/v1/admin/orders/{$order->order_number}/refund", [
            'amount' => 1000,
            'reason' => 'Unauthorized refund attempt',
        ]);

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 5. Audit trail — refund recorded
    // -------------------------------------------------------------------------

    /**
     * Issuing a refund must create an audit_logs row with action = refund.issued.
     */
    public function test_05_refund_creates_audit_log_entry(): void
    {
        $superAdmin = User::factory()->create();
        $superRole = Role::where('slug', 'super-admin')->firstOrFail();
        $superAdmin->roles()->attach($superRole);

        $order = Order::create([
            'order_number' => 'ORD-AUDIT-REFUND',
            'user_id' => $this->userA->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 20000,
            'tax_total' => 3600,
            'grand_total' => 23600,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/orders/{$order->order_number}/refund", [
            'amount' => 5000,
            'reason' => 'Customer requested partial refund',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'refund.issued',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. Audit trail — stock adjustment recorded
    // -------------------------------------------------------------------------

    /**
     * A stock adjustment must create an audit_logs row with action = stock.adjusted.
     */
    public function test_06_stock_adjustment_creates_audit_log_entry(): void
    {
        $superAdmin = User::factory()->create();
        $superRole = Role::where('slug', 'super-admin')->firstOrFail();
        $superAdmin->roles()->attach($superRole);

        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Audit Stock Product',
            'slug' => 'audit-stock-product',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'AUDIT-STK-001',
            'name' => 'Bag',
            'mrp' => 10000,
            'selling_price' => 9000,
            'status' => 'active',
        ]);

        $item = InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 50,
            'reserved' => 0,
            'available' => 50,
        ]);

        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/inventory/{$item->id}/adjust", [
            'quantity_change' => 10,
            'reason' => 'Goods received from supplier',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'stock.adjusted',
            'entity_type' => InventoryItem::class,
            'entity_id' => $item->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 7. Audit trail — settings change recorded
    // -------------------------------------------------------------------------

    /**
     * Saving admin settings must create an audit_logs row with action = setting.changed.
     */
    public function test_07_settings_change_creates_audit_log_entry(): void
    {
        $superAdmin = User::factory()->create();
        $superRole = Role::where('slug', 'super-admin')->firstOrFail();
        $superAdmin->roles()->attach($superRole);

        $this->actingAs($superAdmin);

        $this->post(route('admin.settings.update'), [
            'store_name' => 'Updated Store Name',
            'store_email' => 'updated@store.test',
            'store_phone' => '+91 98765 43210',
            'store_address' => '42 Commerce Lane, Bengaluru',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'default_tax_rate' => 18,
            'prices_include_tax' => '1',
            'min_order_amount' => 0,
            'free_shipping_threshold' => 5000,
            'standard_shipping_rate' => 150,
            'express_shipping_rate' => 350,
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'setting.changed',
            'entity_type' => 'SystemSetting',
        ]);
    }

    // -------------------------------------------------------------------------
    // 8. Unauthenticated API access — 401 on protected endpoints
    // -------------------------------------------------------------------------

    /**
     * All authenticated API endpoints must return 401 for unauthenticated requests.
     */
    public function test_08_unauthenticated_requests_are_rejected_on_protected_api_endpoints(): void
    {
        // Customer order list — requires auth
        $this->getJson('/api/v1/orders')->assertStatus(401);

        // Wishlist — requires auth
        $this->getJson('/api/v1/wishlist')->assertStatus(401);

        // Admin refund — requires auth + permission
        $this->postJson('/api/v1/admin/orders/ORD-FAKE/refund', [
            'amount' => 100,
            'reason' => 'Test',
        ])->assertStatus(401);
    }
}
