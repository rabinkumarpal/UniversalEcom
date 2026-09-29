<?php

namespace Tests\Feature;

use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorOrder;
use Tests\TestCase;

class VendorMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_vendor_cart_order_splitting_and_commission_calculation(): void
    {
        $customer = User::factory()->create();
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'base_fee' => 0]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        $address = Address::create([
            'user_id' => $customer->id,
            'recipient_name' => 'Builder Corp',
            'phone' => '9988776655',
            'address_line_1' => 'Project Site A',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        $category = Category::create(['name' => 'Building Materials', 'slug' => 'building-materials']);
        $brand = Brand::create(['name' => 'UltraBuild', 'slug' => 'ultrabuild']);

        // Vendor A (10% commission)
        $vendorA = Vendor::create([
            'legal_name' => 'Alpha Suppliers Ltd',
            'display_name' => 'Alpha Building Supplies',
            'slug' => 'alpha-suppliers',
            'email' => 'sales@alpha.com',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 10.00,
        ]);

        // Vendor B (15% commission)
        $vendorB = Vendor::create([
            'legal_name' => 'Beta Paints & Finishes',
            'display_name' => 'Beta Paints Direct',
            'slug' => 'beta-paints',
            'email' => 'contact@beta.com',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 15.00,
        ]);

        // Product A sold by Vendor A: Sand Bag (₹200 = 20,000 cents)
        $prodA = Product::create(['primary_category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'River Sand', 'slug' => 'river-sand', 'status' => 'published']);
        $varA = ProductVariant::create(['product_id' => $prodA->id, 'sku' => 'SAND-01', 'name' => '50kg Bag', 'mrp' => 25000, 'selling_price' => 20000, 'status' => 'active']);
        VendorOffer::create(['vendor_id' => $vendorA->id, 'product_variant_id' => $varA->id, 'vendor_price' => 20000, 'status' => 'approved']);

        // Product B sold by Vendor B: Exterior Emulsion Paint (₹1,000 = 100,000 cents)
        $prodB = Product::create(['primary_category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'Exterior Paint 10L', 'slug' => 'exterior-paint-10l', 'status' => 'published']);
        $varB = ProductVariant::create(['product_id' => $prodB->id, 'sku' => 'PAINT-10L', 'name' => '10 Litre Bucket', 'mrp' => 120000, 'selling_price' => 100000, 'status' => 'active']);
        VendorOffer::create(['vendor_id' => $vendorB->id, 'product_variant_id' => $varB->id, 'vendor_price' => 100000, 'status' => 'approved']);

        $wh = Warehouse::create(['code' => 'WH-MKT', 'name' => 'Marketplace Warehouse', 'is_active' => true]);
        InventoryItem::create(['warehouse_id' => $wh->id, 'product_variant_id' => $varA->id, 'on_hand' => 100, 'available' => 100]);
        InventoryItem::create(['warehouse_id' => $wh->id, 'product_variant_id' => $varB->id, 'on_hand' => 50, 'available' => 50]);

        // Customer adds items from BOTH Vendor A and Vendor B to their single cart
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($customer);

        // 5 bags of Sand from Vendor A = 5 * 20,000 = 100,000
        $cartService->addItem($cart, $varA->id, 5);
        // 2 buckets of Paint from Vendor B = 2 * 100,000 = 200,000
        $cartService->addItem($cart, $varB->id, 2);

        // Customer checkout: Total = 300,000
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->checkout($cart, $address, $address, 'cod', 'Mixed vendor order', $customer);

        // Assert Customer Order
        $this->assertEquals(300000, $order->subtotal);
        $this->assertEquals(300000, $order->grand_total);
        $this->assertEquals(2, $order->items->count());

        // Assert Order Splitting: Check Vendor Orders
        $vendorOrders = VendorOrder::where('order_id', $order->id)->get();
        $this->assertCount(2, $vendorOrders);

        // Vendor A order slice:
        $voA = $vendorOrders->firstWhere('vendor_id', $vendorA->id);
        $this->assertNotNull($voA);
        $this->assertEquals(100000, $voA->subtotal);
        $this->assertEquals(10000, $voA->commission_amount); // 10% of 100,000 = 10,000
        $this->assertEquals(90000, $voA->vendor_payout); // 100,000 - 10,000 = 90,000
        $this->assertEquals(1, $voA->items()->count());

        // Vendor B order slice:
        $voB = $vendorOrders->firstWhere('vendor_id', $vendorB->id);
        $this->assertNotNull($voB);
        $this->assertEquals(200000, $voB->subtotal);
        $this->assertEquals(30000, $voB->commission_amount); // 15% of 200,000 = 30,000
        $this->assertEquals(170000, $voB->vendor_payout); // 200,000 - 30,000 = 170,000
        $this->assertEquals(1, $voB->items()->count());
    }
}
