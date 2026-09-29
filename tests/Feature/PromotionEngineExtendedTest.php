<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Packages\PromotionEngine\Models\Promotion;
use Packages\PromotionEngine\Models\PromotionBundleItem;
use Packages\PromotionEngine\Models\PromotionMixMatchItem;
use Packages\PromotionEngine\Services\PromotionCalculationService;
use Tests\TestCase;

/**
 * Tests for the extended promotion engine covering bundle, mix & match,
 * buy_x_get_y_bundle, countdown, stock_scarcity, stacking and priority.
 */
class PromotionEngineExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionCalculationService $engine;

    protected TaxClass $taxClass;

    protected Brand $brand;

    protected Category $category;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(PromotionCalculationService::class);

        $this->taxClass = TaxClass::create(['name' => 'GST 18%', 'code' => 'GST18_PE', 'rate' => 1800]);
        $this->brand = Brand::create(['name' => 'PEBrand', 'slug' => 'pe-brand', 'is_active' => true]);
        $this->category = Category::create(['name' => 'PECat', 'slug' => 'pe-cat', 'is_active' => true]);
        $this->warehouse = Warehouse::create(['code' => 'PE-WH', 'name' => 'PE Warehouse', 'is_active' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeVariant(string $sku, int $sellingPrice): ProductVariant
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => "Product {$sku}",
            'slug' => 'product-'.strtolower($sku).'-'.Str::random(4),
            'status' => 'active',
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => $sku,
            'name' => "Variant {$sku}",
            'mrp' => $sellingPrice + 1000,
            'selling_price' => $sellingPrice,
            'status' => 'active',
        ]);
    }

    /**
     * Build a minimal Cart with CartItems and return the engine-compatible context lines array.
     *
     * @param  array<int, int>  $variantQtys  [variant_id => quantity]
     * @return array{cart: Cart, lines: array<int, array<string, mixed>>}
     */
    private function makeCartWithLines(array $variantQtys): array
    {
        $cart = Cart::create([
            'session_token' => Str::random(32),
            'raw_subtotal' => 0,
        ]);

        $lines = [];
        $subtotal = 0;

        foreach ($variantQtys as $variantId => $qty) {
            $variant = ProductVariant::findOrFail($variantId);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variantId,
                'quantity' => $qty,
                'unit_price' => $variant->selling_price,
            ]);

            $baseTotal = $variant->selling_price * $qty;
            $subtotal += $baseTotal;
            $lines[$variantId] = [
                'quantity' => $qty,
                'unit_price' => $variant->selling_price,
                'base_total' => $baseTotal,
            ];
        }

        $cart->update(['raw_subtotal' => $subtotal]);

        return ['cart' => $cart, 'lines' => $lines];
    }

    // =========================================================================
    // 1. Bundle discount — all required items present → discount applied
    // =========================================================================

    public function test_01_bundle_discount_applies_when_all_required_items_are_in_cart(): void
    {
        $cement = $this->makeVariant('BNDL-CEM', 45000); // ₹450
        $sand = $this->makeVariant('BNDL-SND', 30000); // ₹300

        $promo = Promotion::create([
            'name' => 'Cement + Sand Bundle',
            'slug' => 'cement-sand-bundle',
            'type' => 'bundle',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'configuration' => ['discount_percentage' => 10],
        ]);

        PromotionBundleItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $cement->id, 'required_quantity' => 1]);
        PromotionBundleItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $sand->id,   'required_quantity' => 1]);

        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$cement->id => 2, $sand->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertGreaterThan(0, $result['discount_total'], 'Bundle discount should be applied');
        $this->assertCount(1, $result['applied_promotions']);
        $this->assertEquals('bundle', $result['applied_promotions'][0]['type']);
    }

    // =========================================================================
    // 2. Bundle not triggered — missing one required item
    // =========================================================================

    public function test_02_bundle_discount_not_applied_when_required_item_missing(): void
    {
        $cement = $this->makeVariant('BNDL2-CEM', 45000);
        $sand = $this->makeVariant('BNDL2-SND', 30000);

        $promo = Promotion::create([
            'name' => 'Cement + Sand Bundle 2',
            'slug' => 'cement-sand-bundle-2',
            'type' => 'bundle',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'configuration' => ['discount_percentage' => 10],
        ]);

        PromotionBundleItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $cement->id, 'required_quantity' => 1]);
        PromotionBundleItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $sand->id,   'required_quantity' => 1]);

        // Cart has only cement — sand is missing
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$cement->id => 2]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertEquals(0, $result['discount_total'], 'Bundle should not trigger if a required item is absent');
        $this->assertEmpty($result['applied_promotions']);
    }

    // =========================================================================
    // 3. Mix & Match — sufficient quantity from pool → discount applied
    // =========================================================================

    public function test_03_mix_match_applies_when_minimum_quantity_met(): void
    {
        $paint = $this->makeVariant('MIX-PAINT', 80000);
        $primer = $this->makeVariant('MIX-PRIMR', 60000);
        $roller = $this->makeVariant('MIX-ROLL', 20000);

        $promo = Promotion::create([
            'name' => 'Mix & Match Tools',
            'slug' => 'mix-match-tools',
            'type' => 'mix_match',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'configuration' => ['min_quantity' => 3, 'discount_percentage' => 10],
        ]);

        PromotionMixMatchItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $paint->id]);
        PromotionMixMatchItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $primer->id]);
        PromotionMixMatchItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $roller->id]);

        // Cart has 3 eligible items (1+1+1)
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$paint->id => 1, $primer->id => 1, $roller->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertGreaterThan(0, $result['discount_total']);
        $this->assertEquals('mix_match', $result['applied_promotions'][0]['type']);
    }

    // =========================================================================
    // 4. Mix & Match — insufficient quantity → no discount
    // =========================================================================

    public function test_04_mix_match_not_applied_when_quantity_insufficient(): void
    {
        $paint = $this->makeVariant('MIX2-PAINT', 80000);
        $roller = $this->makeVariant('MIX2-ROLL', 20000);

        $promo = Promotion::create([
            'name' => 'Mix & Match Insufficient',
            'slug' => 'mix-match-insufficient',
            'type' => 'mix_match',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'configuration' => ['min_quantity' => 3, 'discount_percentage' => 10],
        ]);

        PromotionMixMatchItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $paint->id]);
        PromotionMixMatchItem::create(['promotion_id' => $promo->id, 'product_variant_id' => $roller->id]);

        // Only 2 items from pool — min is 3
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$paint->id => 1, $roller->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertEquals(0, $result['discount_total']);
        $this->assertEmpty($result['applied_promotions']);
    }

    // =========================================================================
    // 5. Buy X Get Y Bundle — qualifying lines present → reward item free
    // =========================================================================

    public function test_05_buy_x_get_y_bundle_gives_reward_item_free(): void
    {
        $cement = $this->makeVariant('BXY-CEM', 45000);
        $wp = $this->makeVariant('BXY-WP', 35000);
        $trowel = $this->makeVariant('BXY-TRW', 15000); // reward

        $promo = Promotion::create([
            'name' => 'Buy 2 Cement + 1 WP → Trowel Free',
            'slug' => 'bxy-bundle-trowel',
            'type' => 'buy_x_get_y_bundle',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'configuration' => [
                'qualifying_lines' => [
                    ['variant_id' => $cement->id, 'quantity' => 2],
                    ['variant_id' => $wp->id,     'quantity' => 1],
                ],
                'reward_variant_id' => $trowel->id,
                'reward_quantity' => 1,
            ],
        ]);

        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([
            $cement->id => 2,
            $wp->id => 1,
            $trowel->id => 1, // reward item in cart
        ]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertGreaterThan(0, $result['discount_total'], 'Reward item should be discounted to zero');
        $this->assertEquals(15000, $result['discount_total'], 'Discount should equal one trowel price');
        $this->assertEquals('buy_x_get_y_bundle', $result['applied_promotions'][0]['type']);
    }

    // =========================================================================
    // 6. Countdown — promotion outside window → not applied
    // =========================================================================

    public function test_06_countdown_promotion_does_not_apply_after_expiry(): void
    {
        Promotion::create([
            'name' => 'Expired Countdown',
            'slug' => 'expired-countdown',
            'type' => 'countdown',
            'status' => 'active',
            'priority' => 10,
            'stackable' => false,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDay(), // ended yesterday
            'configuration' => ['discount_percentage' => 20],
        ]);

        $v = $this->makeVariant('COUNT-EXP', 50000);
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$v->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertEquals(0, $result['discount_total'], 'Expired countdown must not apply');
        $this->assertEmpty($result['applied_promotions']);
    }

    // =========================================================================
    // 7. Stock scarcity — variant below threshold → signal returned
    // =========================================================================

    public function test_07_stock_scarcity_signal_returned_for_low_stock_variants(): void
    {
        $v = $this->makeVariant('SCRC-001', 50000);

        InventoryItem::create([
            'product_variant_id' => $v->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 4,
            'reserved' => 0,
            'available' => 4,
        ]);

        Promotion::create([
            'name' => 'Low Stock Alert',
            'slug' => 'low-stock-alert',
            'type' => 'stock_scarcity',
            'status' => 'active',
            'priority' => 99,
            'stackable' => true,
            'configuration' => ['threshold' => 10, 'eligible_variant_ids' => [$v->id]],
        ]);

        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$v->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        $this->assertArrayHasKey($v->id, $result['scarcity_signals']);
        $this->assertEquals(4, $result['scarcity_signals'][$v->id]['available']);
        $this->assertStringContainsString('4', $result['scarcity_signals'][$v->id]['label']);
        $this->assertEquals(0, $result['discount_total'], 'Stock scarcity must never create a discount');
    }

    // =========================================================================
    // 8. Stacking — two stackable promos both apply
    // =========================================================================

    public function test_08_two_stackable_promotions_both_apply(): void
    {
        Promotion::create([
            'name' => 'Stackable 5%',
            'slug' => 'stackable-5-pct',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 1,
            'stackable' => true,
            'configuration' => ['discount_percentage' => 5],
        ]);

        Promotion::create([
            'name' => 'Stackable Fixed 10000',
            'slug' => 'stackable-fixed-10k',
            'type' => 'fixed',
            'status' => 'active',
            'priority' => 2,
            'stackable' => true,
            'configuration' => ['fixed_amount' => 10000],
        ]);

        $v = $this->makeVariant('STACK-001', 100000); // ₹1,000
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$v->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        // 5% of 100000 = 5000, plus fixed 10000 → total 15000
        $this->assertEquals(15000, $result['discount_total']);
        $this->assertCount(2, $result['applied_promotions']);
    }

    // =========================================================================
    // 9. Non-stackable blocks subsequent promotions
    // =========================================================================

    public function test_09_non_stackable_promotion_blocks_subsequent_promotions(): void
    {
        Promotion::create([
            'name' => 'Non-Stackable 10%',
            'slug' => 'non-stackable-10-pct',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 1,
            'stackable' => false, // stops the queue after applying
            'configuration' => ['discount_percentage' => 10],
        ]);

        Promotion::create([
            'name' => 'Would-Be Second 5%',
            'slug' => 'would-be-second-5-pct',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 2,
            'stackable' => true,
            'configuration' => ['discount_percentage' => 5],
        ]);

        $v = $this->makeVariant('NSTACK-001', 100000); // ₹1,000
        ['cart' => $cart, 'lines' => $lines] = $this->makeCartWithLines([$v->id => 1]);

        $result = $this->engine->calculateCartAdjustments($cart, ['current_lines' => $lines]);

        // Only the first 10% should apply (10,000 paise), second blocked
        $this->assertEquals(10000, $result['discount_total']);
        $this->assertCount(1, $result['applied_promotions']);
    }
}
