<?php

namespace Tests\Unit;

use App\Domain\Inventory\InventoryService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_reservation_and_oversell_prevention(): void
    {
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Widget',
            'slug' => 'widget',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'WIDGET-STOCK',
            'name' => 'Stock Unit',
            'mrp' => 1000,
            'selling_price' => 1000,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Central Depot',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 10,
            'reserved' => 0,
            'available' => 10,
        ]);

        $service = app(InventoryService::class);

        // Check availability
        $this->assertTrue($service->checkAvailability($variant, 5));
        $this->assertFalse($service->checkAvailability($variant, 15));

        // Reserve 4 units
        $res = $service->reserveStock($variant, 4);
        $item->refresh();
        $this->assertEquals(4, $item->reserved);
        $this->assertEquals(6, $item->available);

        // Attempt to reserve 7 units (which exceeds available 6) -> must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $service->reserveStock($variant, 7);
    }

    public function test_inventory_reservation_release(): void
    {
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'name' => 'Widget 2',
            'slug' => 'widget-2',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'WIDGET-REL',
            'name' => 'Stock Unit',
            'mrp' => 1000,
            'selling_price' => 1000,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-02',
            'name' => 'North Depot',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 10,
            'reserved' => 0,
            'available' => 10,
        ]);

        $service = app(InventoryService::class);
        $reservation = $service->reserveStock($variant, 5);
        $item->refresh();
        $this->assertEquals(5, $item->available);

        $service->releaseReservation($reservation);
        $item->refresh();
        $this->assertEquals(0, $item->reserved);
        $this->assertEquals(10, $item->available);
    }
}
