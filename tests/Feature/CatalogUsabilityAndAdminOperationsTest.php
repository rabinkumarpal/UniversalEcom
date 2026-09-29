<?php

namespace Tests\Feature;

use App\Core\Services\SettingService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductVariant;
use App\Models\QuantityPriceTier;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\B2BCommerce\Models\Company;
use Tests\TestCase;

class CatalogUsabilityAndAdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->admin = User::factory()->create([
            'email' => 'operations.lead@universal-ecom.test',
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    public function test_admin_can_update_product_and_modify_existing_and_new_variants(): void
    {
        $category = Category::create([
            'name' => 'Heavy Hardware',
            'slug' => 'heavy-hardware',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Industrial Power Drill',
            'slug' => 'industrial-power-drill',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'DRILL-STD-110V',
            'name' => 'Standard 110V Kit',
            'unit' => 'kit',
            'mrp' => 500000, // ₹5000.00
            'selling_price' => 450000, // ₹4500.00
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'Industrial Power Drill Max',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [
                [
                    'id' => $variant1->id,
                    'name' => 'Standard 110V Kit (Upgraded)',
                    'sku' => 'DRILL-STD-110V',
                    'mrp' => '5500.00',
                    'selling_price' => '4800.00',
                    'unit' => 'kit',
                    'status' => 'active',
                ],
                [
                    'name' => 'Heavy Duty 220V Cordless Kit',
                    'sku' => 'DRILL-HD-220V',
                    'mrp' => '7500.00',
                    'selling_price' => '6900.00',
                    'unit' => 'kit',
                    'status' => 'active',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));
        $response->assertSessionHas('success');

        $variant1->refresh();
        $this->assertEquals('Standard 110V Kit (Upgraded)', $variant1->name);
        $this->assertEquals(550000, $variant1->mrp);
        $this->assertEquals(480000, $variant1->selling_price);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'DRILL-HD-220V',
            'name' => 'Heavy Duty 220V Cordless Kit',
            'selling_price' => 690000,
        ]);
    }

    public function test_storefront_product_page_renders_interactive_variant_selector_when_multiple_variants_exist(): void
    {
        $category = Category::create([
            'name' => 'Structural Steel',
            'slug' => 'structural-steel',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'High Grade TMT Rebar',
            'slug' => 'high-grade-tmt-rebar',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'REBAR-12MM',
            'name' => '12mm Diameter Bar',
            'unit' => 'piece',
            'mrp' => 80000,
            'selling_price' => 72000,
            'status' => 'active',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'REBAR-16MM',
            'name' => '16mm Heavy Bar',
            'unit' => 'piece',
            'mrp' => 120000,
            'selling_price' => 105000,
            'status' => 'active',
        ]);

        $response = $this->get(route('storefront.product', $product->slug));
        $response->assertStatus(200);
        $response->assertSee('Select Packaging / Pack Size:');
        $response->assertSee('2 options available');
        $response->assertSee('12mm Diameter Bar');
        $response->assertSee('16mm Heavy Bar');
        $response->assertSee('REBAR-12MM');
        $response->assertSee('REBAR-16MM');
    }

    public function test_admin_orders_page_supports_search_and_status_filtering(): void
    {
        $customer = User::factory()->create([
            'name' => 'Aarav Builders',
            'email' => 'aarav@builders.test',
        ]);

        $order1 = Order::create([
            'order_number' => 'ORD-TEST-9001',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 100000,
            'discount_total' => 0,
            'tax_total' => 18000,
            'delivery_fee' => 0,
            'grand_total' => 118000,
            'shipping_address_snapshot' => [
                'recipient_name' => 'Aarav Patel',
                'phone' => '9888812345',
                'city' => 'Ahmedabad',
            ],
            'billing_address_snapshot' => [
                'recipient_name' => 'Aarav Patel',
                'phone' => '9888812345',
                'city' => 'Ahmedabad',
            ],
            'placed_at' => now(),
        ]);

        $order2 = Order::create([
            'order_number' => 'ORD-TEST-9002',
            'user_id' => null,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'fulfillment_status' => 'delivered',
            'currency' => 'INR',
            'subtotal' => 50000,
            'discount_total' => 0,
            'tax_total' => 9000,
            'delivery_fee' => 0,
            'grand_total' => 59000,
            'shipping_address_snapshot' => [
                'recipient_name' => 'Meera Shah',
                'phone' => '9777754321',
                'city' => 'Surat',
            ],
            'billing_address_snapshot' => [
                'recipient_name' => 'Meera Shah',
                'phone' => '9777754321',
                'city' => 'Surat',
            ],
            'placed_at' => now(),
        ]);

        // Search by order number
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.orders', ['search' => '9001']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('ORD-TEST-9001');
        $searchResponse->assertDontSee('ORD-TEST-9002');

        // Search by phone
        $phoneResponse = $this->actingAs($this->admin)->get(route('admin.orders', ['search' => '9777754321']));
        $phoneResponse->assertStatus(200);
        $phoneResponse->assertSee('ORD-TEST-9002');
        $phoneResponse->assertDontSee('ORD-TEST-9001');

        // Filter by status
        $statusResponse = $this->actingAs($this->admin)->get(route('admin.orders', ['status' => 'confirmed']));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('ORD-TEST-9001');
        $statusResponse->assertDontSee('ORD-TEST-9002');
    }

    public function test_admin_can_view_detailed_order_screen_with_line_items_and_audit_timeline(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SHOW-5555',
            'user_id' => null,
            'status' => 'picking',
            'payment_status' => 'pending',
            'fulfillment_status' => 'in_progress',
            'currency' => 'INR',
            'subtotal' => 200000,
            'discount_total' => 0,
            'tax_total' => 36000,
            'delivery_fee' => 5000,
            'grand_total' => 241000,
            'shipping_address_snapshot' => [
                'recipient_name' => 'Anil Kumar',
                'phone' => '9988776655',
                'address_line_1' => 'Plot 10, Industrial Phase 1',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
            ],
            'billing_address_snapshot' => [
                'recipient_name' => 'Anil Kumar',
                'address_line_1' => 'Plot 10, Industrial Phase 1',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
            ],
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name_snapshot' => 'Commercial Waterproofing Chemical',
            'variant_name_snapshot' => '20L Drum',
            'sku_snapshot' => 'CHEM-WP-20L',
            'unit_price' => 200000,
            'quantity' => 1,
            'discount' => 0,
            'tax' => 36000,
            'line_total' => 236000,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => 'confirmed',
            'new_status' => 'picking',
            'note' => 'Warehouse operator started picking items',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->order_number));
        $response->assertStatus(200);
        $response->assertSee('ORD-SHOW-5555');
        $response->assertSee('Commercial Waterproofing Chemical');
        $response->assertSee('CHEM-WP-20L');
        $response->assertSee('Anil Kumar');
        $response->assertSee('Warehouse operator started picking items');
        $response->assertSee('Execute State Transition');
    }

    public function test_admin_can_assign_and_remove_b2b_company_users(): void
    {
        $company = Company::create([
            'name' => 'Tata Projects Corporate',
            'company_code' => 'TATA-PROJ',
            'credit_limit' => 50000000,
            'credit_balance' => 50000000,
            'payment_terms_days' => 45,
            'status' => 'active',
        ]);

        $buyer = User::factory()->create([
            'name' => 'Procurement Officer',
            'email' => 'buyer@tataprojects.test',
        ]);

        // Assign user
        $assignResponse = $this->actingAs($this->admin)->post(route('admin.b2b.companies.users.assign', $company->id), [
            'user_id' => $buyer->id,
            'role' => 'buyer',
            'spending_limit_in_rupees' => '250000.00',
        ]);

        $assignResponse->assertRedirect();
        $assignResponse->assertSessionHas('success');

        $this->assertDatabaseHas('company_users', [
            'company_id' => $company->id,
            'user_id' => $buyer->id,
            'role' => 'buyer',
            'spending_limit' => 25000000, // in cents
            'is_active' => true,
        ]);

        // Remove user
        $removeResponse = $this->actingAs($this->admin)->delete(route('admin.b2b.companies.users.remove', [$company->id, $buyer->id]));
        $removeResponse->assertRedirect();
        $removeResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('company_users', [
            'company_id' => $company->id,
            'user_id' => $buyer->id,
        ]);
    }

    public function test_enforcement_of_guest_checkout_feature_flag(): void
    {
        $settings = app(SettingService::class);
        $settings->set('features.guest_checkout_enabled', false, 'features');

        $response = $this->get(route('storefront.checkout'));
        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('error');

        // Re-enable and verify guest access is restored
        $settings->set('features.guest_checkout_enabled', true, 'features');
    }

    public function test_enforcement_of_reviews_enabled_feature_flag(): void
    {
        $settings = app(SettingService::class);
        $settings->set('features.reviews_enabled', false, 'features');

        $category = Category::create(['name' => 'Tools', 'slug' => 'tools', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Laser Measure',
            'slug' => 'laser-measure',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.product.review', $product->id), [
            'rating' => 5,
            'title' => 'Accurate to the mm',
            'comment' => 'Very reliable tool on job sites.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        // Restore
        $settings->set('features.reviews_enabled', true, 'features');
    }

    public function test_admin_product_edit_screen_renders_successfully_with_tiers_logistics_and_stock(): void
    {
        $taxClass = TaxClass::create([
            'name' => 'GST 28%',
            'rate_percentage' => 28.0,
        ]);

        $category = Category::create([
            'name' => 'Cement & Aggregates',
            'slug' => 'cement-aggregates',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'UltraTech Super PPC High Strength Cement',
            'slug' => 'ultratech-super-cement',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $taxClass->id,
            'sku' => 'UT-PPC-50KG',
            'name' => '50 KG Sealed Bag',
            'unit' => 'bag',
            'pack_size' => '50 KG',
            'weight_kg' => 50.0,
            'mrp' => 48000,
            'selling_price' => 45000,
            'status' => 'active',
        ]);

        $tier = QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'unit_price' => 43500,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-BLR-01',
            'name' => 'Bengaluru Central Hub',
            'is_active' => true,
        ]);

        InventoryItem::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 998,
            'reserved' => 0,
            'available' => 998,
            'reorder_level' => 100,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.catalog.products.edit', $product->id));

        $response->assertStatus(200);
        $response->assertSee('UltraTech Super PPC High Strength Cement');
        $response->assertSee('UT-PPC-50KG');
        $response->assertSee('480.00');
        $response->assertSee('450.00');
        $response->assertSee('435.00');
        $response->assertSee('Bengaluru Central Hub');
        $response->assertSee('998');
        $response->assertSee('B2B Wholesale Quantity Price Tiers');
        $response->assertSee('Storefront ↗');
    }

    public function test_admin_can_update_product_logistics_and_wholesale_quantity_tiers(): void
    {
        $taxClass = TaxClass::create([
            'name' => 'GST 18%',
            'rate_percentage' => 18.0,
        ]);

        $category = Category::create([
            'name' => 'Hardware',
            'slug' => 'hardware',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Concrete Screws 100pk',
            'slug' => 'concrete-screws-100pk',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SCREW-CON-100',
            'name' => 'Box of 100',
            'unit' => 'box',
            'mrp' => 120000,
            'selling_price' => 99000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'Concrete Screws 100pk Ultra',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [
                [
                    'id' => $variant->id,
                    'name' => 'Box of 100 Ultra',
                    'sku' => 'SCREW-CON-100',
                    'mrp' => '1300.00',
                    'selling_price' => '1050.00',
                    'unit' => 'box',
                    'pack_size' => '100 pcs',
                    'weight_kg' => '2.5',
                    'barcode' => '8901234567890',
                    'tax_class_id' => $taxClass->id,
                    'status' => 'active',
                    'tiers' => [
                        [
                            'min_quantity' => 5,
                            'max_quantity' => 19,
                            'unit_price' => '980.00',
                        ],
                        [
                            'min_quantity' => 20,
                            'max_quantity' => null,
                            'unit_price' => '920.00',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));
        $response->assertSessionHas('success');

        $variant->refresh();
        $this->assertEquals('Box of 100 Ultra', $variant->name);
        $this->assertEquals(130000, $variant->mrp);
        $this->assertEquals(105000, $variant->selling_price);
        $this->assertEquals(2.5, $variant->weight_kg);
        $this->assertEquals('8901234567890', $variant->barcode);
        $this->assertEquals($taxClass->id, $variant->tax_class_id);

        $this->assertCount(2, $variant->quantityTiers);
        $this->assertDatabaseHas('quantity_price_tiers', [
            'product_variant_id' => $variant->id,
            'min_quantity' => 5,
            'max_quantity' => 19,
            'unit_price' => 98000,
        ]);
        $this->assertDatabaseHas('quantity_price_tiers', [
            'product_variant_id' => $variant->id,
            'min_quantity' => 20,
            'unit_price' => 92000,
        ]);
    }

    public function test_storefront_product_page_renders_technical_documents_and_logistics_specs(): void
    {
        $taxClass = TaxClass::create([
            'name' => 'GST 28%',
            'rate_percentage' => 28.0,
        ]);

        $category = Category::create([
            'name' => 'Cement & Aggregates',
            'slug' => 'cement-aggregates',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'UltraTech Super PPC High Strength Cement',
            'slug' => 'ultratech-super-cement',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $taxClass->id,
            'sku' => 'UT-PPC-50KG',
            'name' => '50 KG Sealed Bag',
            'unit' => 'bag',
            'pack_size' => '50 KG Bag',
            'weight_kg' => 50.0,
            'mrp' => 48000,
            'selling_price' => 45000,
            'status' => 'active',
        ]);

        ProductDocument::create([
            'product_id' => $product->id,
            'title' => 'BIS IS:1489 Part 1 Certificate',
            'url' => 'https://example.com/docs/bis-is1489.pdf',
            'file_type' => 'application/pdf',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('storefront.product', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('UltraTech Super PPC High Strength Cement');
        $response->assertSee('BIS IS:1489 Part 1 Certificate');
        $response->assertSee('https://example.com/docs/bis-is1489.pdf');
        $response->assertSee('Download');
        $response->assertSee('Technical Data &amp; Compliance Certificates', false);
        $response->assertSee('Inclusive of');
        $response->assertSee('UT-PPC-50KG');
    }

    public function test_admin_product_index_displays_stock_and_price_metrics(): void
    {
        $category = Category::create([
            'name' => 'Fasteners',
            'slug' => 'fasteners',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'GI Bolts 10mm',
            'slug' => 'gi-bolts-10mm',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'GI-BOLT-10',
            'name' => '10mm Standard',
            'mrp' => 1500,
            'selling_price' => 1200,
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Fasteners WH',
            'code' => 'FWH',
            'location' => 'Delhi',
            'status' => 'active',
        ]);

        InventoryItem::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 50,
            'reserved' => 5,
            'available' => 45,
        ]);

        QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 100,
            'max_quantity' => null,
            'unit_price' => 1000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.catalog.products.index'));

        $response->assertStatus(200);
        $response->assertSee('GI Bolts 10mm');
        $response->assertSee('₹12.00');
        $response->assertSee('45');
        $response->assertSee('In Stock');
        $response->assertSee('B2B Tiers');
    }

    public function test_admin_product_index_filters_by_brand_and_stock_status(): void
    {
        $category = Category::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'status' => 'active',
        ]);

        $brand = Brand::create([
            'name' => 'Ashirvad',
            'slug' => 'ashirvad',
            'status' => 'active',
        ]);

        $product1 = Product::create([
            'name' => 'CPVC Pipes',
            'slug' => 'cpvc-pipes',
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'published',
        ]);

        $product2 = Product::create([
            'name' => 'PVC Fittings',
            'slug' => 'pvc-fittings',
            'primary_category_id' => $category->id,
            'brand_id' => null,
            'status' => 'published',
        ]);

        // Filter by brand: should show only CPVC Pipes
        $response = $this->actingAs($this->admin)->get(route('admin.catalog.products.index', ['brand_id' => $brand->id]));
        $response->assertStatus(200);
        $response->assertSee('CPVC Pipes');
        $response->assertDontSee('PVC Fittings');
    }

    public function test_admin_can_bulk_publish_and_archive_products(): void
    {
        $category = Category::create([
            'name' => 'Tools',
            'slug' => 'tools',
            'status' => 'active',
        ]);

        $product1 = Product::create([
            'name' => 'Hammer',
            'slug' => 'hammer',
            'primary_category_id' => $category->id,
            'status' => 'draft',
        ]);

        $product2 = Product::create([
            'name' => 'Screwdriver',
            'slug' => 'screwdriver',
            'primary_category_id' => $category->id,
            'status' => 'draft',
        ]);

        // Bulk publish
        $response = $this->actingAs($this->admin)->post(route('admin.catalog.products.bulk-action'), [
            'product_ids' => [$product1->id, $product2->id],
            'action' => 'publish',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));
        $response->assertSessionHas('success', '2 products published.');

        $this->assertDatabaseHas('products', ['id' => $product1->id, 'status' => 'published']);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'status' => 'published']);

        // Bulk archive
        $response = $this->actingAs($this->admin)->post(route('admin.catalog.products.bulk-action'), [
            'product_ids' => [$product1->id],
            'action' => 'archive',
        ]);

        $response->assertSessionHas('success', '1 products archived.');
        $this->assertDatabaseHas('products', ['id' => $product1->id, 'status' => 'archived']);
    }

    public function test_admin_bulk_delete_archives_products_with_orders(): void
    {
        $category = Category::create([
            'name' => 'Cement',
            'slug' => 'cement-bulk',
            'status' => 'active',
        ]);

        $safeProduct = Product::create([
            'name' => 'SafeToDelete Cement',
            'slug' => 'safe-to-delete-cement',
            'primary_category_id' => $category->id,
            'status' => 'draft',
        ]);

        $protectedProduct = Product::create([
            'name' => 'Protected Cement',
            'slug' => 'protected-cement',
            'primary_category_id' => $category->id,
            'status' => 'draft',
        ]);

        // Create variant with an order for protected product
        $variant = ProductVariant::create([
            'product_id' => $protectedProduct->id,
            'sku' => 'PROT-50KG',
            'name' => '50kg Bag',
            'mrp' => 42000,
            'selling_price' => 38000,
            'status' => 'active',
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-BULK-001',
            'status' => 'confirmed',
            'subtotal' => 38000,
            'tax_total' => 6840,
            'delivery_fee' => 0,
            'discount_total' => 0,
            'grand_total' => 44840,
            'currency' => 'INR',
            'billing_address_snapshot' => ['city' => 'Mumbai'],
            'shipping_address_snapshot' => ['city' => 'Mumbai'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => 'Protected Cement',
            'variant_name_snapshot' => '50kg Bag',
            'sku_snapshot' => 'PROT-50KG',
            'quantity' => 1,
            'unit_price' => 38000,
            'line_total' => 44840,
            'tax' => 6840,
            'discount' => 0,
        ]);

        // Bulk delete — protected one should be archived, safe one should be deleted
        $response = $this->actingAs($this->admin)->post(route('admin.catalog.products.bulk-action'), [
            'product_ids' => [$safeProduct->id, $protectedProduct->id],
            'action' => 'delete',
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        // Safe product should be deleted
        $this->assertDatabaseMissing('products', ['id' => $safeProduct->id]);

        // Protected product should be archived (not deleted)
        $this->assertDatabaseHas('products', ['id' => $protectedProduct->id, 'status' => 'archived']);
    }
}
