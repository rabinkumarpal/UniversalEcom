<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
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

class AuthorizationRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Order $order;

    protected InventoryItem $inventoryItem;

    protected Category $category;

    protected Brand $brand;

    protected TaxClass $taxClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RbacSeeder::class);

        $this->brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Cement', 'slug' => 'cement', 'is_active' => true]);
        $this->taxClass = TaxClass::create(['name' => 'GST 18%', 'code' => 'GST_18', 'rate' => 1800]);

        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Standard Portland Cement',
            'slug' => 'standard-portland-cement',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'RBAC-TEST-SKU',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => 1,
            'mrp' => 45000,
            'selling_price' => 41000,
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-RBAC',
            'name' => 'RBAC Warehouse',
            'is_active' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'on_hand' => 100,
            'reserved' => 0,
            'available' => 100,
            'reorder_level' => 10,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-RBAC-001',
            'status' => 'confirmed',
            'payment_status' => 'captured',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 41000,
            'grand_total' => 41000,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 41000,
            'quantity' => 1,
            'line_total' => 41000,
        ]);
    }

    public function test_super_admin_has_bypass_access_to_all_abilities(): void
    {
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $superAdmin->roles()->attach($superAdminRole);

        $this->assertTrue($superAdmin->can('products.create'));
        $this->assertTrue($superAdmin->can('refunds.create'));
        $this->assertTrue($superAdmin->can('inventory.adjust'));
        $this->assertTrue($superAdmin->can('arbitrary.nonexistent.ability'));
    }

    public function test_catalog_manager_can_create_products_but_cannot_issue_refund(): void
    {
        $catalogManager = User::factory()->create();
        $role = Role::where('slug', 'catalog-manager')->firstOrFail();
        $catalogManager->roles()->attach($role);

        Sanctum::actingAs($catalogManager);

        // 1. Can create product
        $createResponse = $this->postJson('/api/v1/admin/products', [
            'name' => 'New Fly Ash Brick Batch',
            'primary_category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'published',
            'description' => 'Tested construction grade brick',
            'variants' => [
                [
                    'sku' => 'BRICK-FLYASH-01',
                    'name' => 'Standard Brick',
                    'unit' => 'piece',
                    'mrp' => 1500,
                    'selling_price' => 1200,
                    'tax_class_id' => $this->taxClass->id,
                ],
            ],
        ]);
        $createResponse->assertCreated();

        // 2. CANNOT issue refund (403 Forbidden as per acceptance criteria)
        $refundResponse = $this->postJson("/api/v1/admin/orders/{$this->order->order_number}/refund", [
            'reason' => 'Customer requested cancellation',
        ]);
        $refundResponse->assertForbidden();
    }

    public function test_order_manager_can_issue_refund_but_cannot_adjust_inventory(): void
    {
        $orderManager = User::factory()->create();
        $role = Role::where('slug', 'order-manager')->firstOrFail();
        $orderManager->roles()->attach($role);

        Sanctum::actingAs($orderManager);

        // 1. CAN issue refund
        $refundResponse = $this->postJson("/api/v1/admin/orders/{$this->order->order_number}/refund", [
            'reason' => 'Damaged transit consignment replacement refunded',
        ]);
        $refundResponse->assertOk();
        $this->assertEquals('refunded', $this->order->fresh()->status);
        $this->assertEquals('refunded', $this->order->fresh()->payment_status);

        // 2. CANNOT adjust inventory stock (403 Forbidden)
        $stockResponse = $this->postJson("/api/v1/admin/inventory/{$this->inventoryItem->id}/adjust", [
            'quantity_change' => 50,
            'reason' => 'Unauthorized stock count update',
        ]);
        $stockResponse->assertForbidden();
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->postJson("/api/v1/admin/orders/{$this->order->order_number}/refund", [
            'reason' => 'Attempt by anonymous attacker',
        ]);

        $response->assertUnauthorized();
    }

    public function test_ensure_role_allows_super_admin_and_admin_variants(): void
    {
        $underscoredSuperAdmin = User::factory()->create();
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Administrator']);
        $underscoredSuperAdmin->roles()->attach($superAdminRole);

        // Underscored super_admin accesses role:admin route
        $response = $this->actingAs($underscoredSuperAdmin)->get(route('admin.communications.whatsapp.index'));
        $response->assertOk();

        // Admin role accesses role:admin route
        $adminUser = User::factory()->create();
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']);
        $adminUser->roles()->attach($adminRole);

        $response = $this->actingAs($adminUser)->get(route('admin.communications.whatsapp.index'));
        $response->assertOk();
    }

    public function test_ensure_role_denies_vendor_user_with_403(): void
    {
        $vendorUser = User::factory()->create();
        $vendorRole = Role::firstOrCreate(['slug' => 'vendor_owner'], ['name' => 'Vendor Owner']);
        $vendorUser->roles()->attach($vendorRole);

        $response = $this->actingAs($vendorUser)->get(route('admin.communications.whatsapp.index'));
        $response->assertForbidden();
        $this->assertStringContainsString('Access denied. Insufficient role privileges.', $response->getContent());
    }
}
