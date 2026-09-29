<?php

namespace Tests\Feature;

use App\Core\Registry\AddonRegistry;
use App\Core\Services\SettingService;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsAndAddonLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'superadmin@universal-ecom.test',
        ]);
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->adminUser->roles()->attach($superAdminRole);
    }

    public function test_setting_service_reads_and_writes_persistent_values(): void
    {
        /** @var SettingService $settings */
        $settings = app(SettingService::class);
        $settings->clearCache();

        // Default fallback
        $this->assertEquals('DEFAULT_VAL', $settings->get('custom.test.key', 'DEFAULT_VAL'));

        // Set value
        $settings->set('custom.test.key', 'PERSISTED_VAL', 'test_group');
        $this->assertEquals('PERSISTED_VAL', $settings->get('custom.test.key'));

        // Verify across cleared cache
        $settings->clearCache();
        $this->assertEquals('PERSISTED_VAL', $settings->get('custom.test.key'));
    }

    public function test_addon_lifecycle_toggle_and_state_determination(): void
    {
        /** @var SettingService $settings */
        $settings = app(SettingService::class);
        $settings->clearCache();

        /** @var AddonRegistry $registry */
        $registry = app(AddonRegistry::class);

        // By default vendor-marketplace is enabled
        $this->assertTrue($registry->isEnabled('vendor-marketplace'));

        // Toggle to disabled
        $settings->toggleAddon('vendor-marketplace', false);
        $this->assertFalse($registry->isEnabled('vendor-marketplace'));

        // Toggle back to enabled
        $settings->toggleAddon('vendor-marketplace', true);
        $this->assertTrue($registry->isEnabled('vendor-marketplace'));
    }

    public function test_admin_can_view_and_update_platform_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.settings'));
        $response->assertOk();
        $response->assertSee('Platform Settings');
        $response->assertSee('Currency, Pricing');

        $updatePayload = [
            'store_name' => 'Apex Mega Store',
            'store_email' => 'apex@universal-ecom.test',
            'store_phone' => '+91 99999 88888',
            'store_address' => 'Apex Industrial Park, Phase 2, Bangalore',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'default_tax_rate' => 12.5,
            'prices_include_tax' => 1,
            'min_order_amount' => 500,
            'free_shipping_threshold' => 10000,
            'standard_shipping_rate' => 200,
            'express_shipping_rate' => 500,
            'reviews_enabled' => 1,
            'wallet_cashback_enabled' => 1,
            'marketplace_enabled' => 1,
            'guest_checkout_enabled' => 0,
        ];

        $postResponse = $this->actingAs($this->adminUser)->post(route('admin.settings.update'), $updatePayload);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        /** @var SettingService $settings */
        $settings = app(SettingService::class);
        $this->assertEquals('Apex Mega Store', $settings->get('store.name'));
        $this->assertEquals(12.5, $settings->get('tax.default_rate'));
        $this->assertEquals(10000, $settings->get('delivery.free_shipping_threshold'));
    }

    public function test_admin_can_toggle_addon_from_addons_console(): void
    {
        $postResponse = $this->actingAs($this->adminUser)->post(route('admin.addons.toggle', 'vendor-marketplace'));
        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        // Check addons page shows state
        $pageResponse = $this->actingAs($this->adminUser)->get(route('admin.addons'));
        $pageResponse->assertOk();
    }
}
