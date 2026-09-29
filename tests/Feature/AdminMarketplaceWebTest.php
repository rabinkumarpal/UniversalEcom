<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorPayout;
use Tests\TestCase;

class AdminMarketplaceWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Administrator']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);
    }

    public function test_guest_and_non_admin_cannot_access_marketplace_admin_hub(): void
    {
        // 1. Guest redirected
        $response = $this->get(route('admin.marketplace.vendors'));
        $response->assertRedirect(route('admin.login'));

        // 2. Regular customer forbidden (403)
        $customer = User::factory()->create();
        $forbiddenResponse = $this->actingAs($customer)->get(route('admin.marketplace.vendors'));
        $forbiddenResponse->assertStatus(403);
    }

    public function test_admin_can_view_vendors_list_and_filter_by_tab_and_search(): void
    {
        $vendor1 = Vendor::create([
            'legal_name' => 'Acme Cement Corporation',
            'display_name' => 'Acme Cement',
            'slug' => 'acme-cement',
            'email' => 'dealer@acmecement.com',
            'phone' => '9876543210',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 10.0,
            'approved_at' => now(),
        ]);

        $vendor2 = Vendor::create([
            'legal_name' => 'BuildTech Global Supplies',
            'display_name' => 'BuildTech Supplies',
            'slug' => 'buildtech-supplies',
            'email' => 'sales@buildtech.test',
            'phone' => '9876543211',
            'status' => 'pending',
            'approval_status' => 'pending',
            'commission_rate_percentage' => 12.5,
        ]);

        $vendor3 = Vendor::create([
            'legal_name' => 'Suspended Tools Inc',
            'display_name' => 'Suspended Tools',
            'slug' => 'suspended-tools',
            'email' => 'info@suspendedtools.test',
            'phone' => '9876543212',
            'status' => 'suspended',
            'approval_status' => 'rejected',
            'commission_rate_percentage' => 15.0,
        ]);

        // Access all vendors
        $response = $this->actingAs($this->admin)->get(route('admin.marketplace.vendors'));
        $response->assertStatus(200);
        $response->assertSee('Acme Cement');
        $response->assertSee('BuildTech Supplies');
        $response->assertSee('Suspended Tools');

        // Filter by Pending Review tab
        $pendingResponse = $this->actingAs($this->admin)->get(route('admin.marketplace.vendors', ['tab' => 'pending_approval']));
        $pendingResponse->assertStatus(200);
        $pendingResponse->assertSee('BuildTech Supplies');
        $pendingResponse->assertDontSee('Acme Cement');

        // Search query
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.marketplace.vendors', ['search' => 'acmecement']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Acme Cement');
        $searchResponse->assertDontSee('BuildTech Supplies');
    }

    public function test_admin_can_quick_approve_vendor(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Apex Steel Ltd',
            'display_name' => 'Apex Steel',
            'slug' => 'apex-steel',
            'email' => 'contact@apexsteel.test',
            'status' => 'pending',
            'approval_status' => 'pending',
            'commission_rate_percentage' => 8.0,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'quick_action' => 'approve',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $vendor->refresh();
        $this->assertEquals('approved', $vendor->approval_status);
        $this->assertEquals('active', $vendor->status);
        $this->assertNotNull($vendor->approved_at);
        $this->assertTrue($vendor->isActive());
    }

    public function test_admin_can_quick_reject_and_suspend_vendor(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Shady Materials Co',
            'display_name' => 'Shady Materials',
            'slug' => 'shady-materials',
            'email' => 'contact@shadymaterials.test',
            'status' => 'pending',
            'approval_status' => 'pending',
            'commission_rate_percentage' => 10.0,
        ]);

        // Quick reject
        $rejectResponse = $this->actingAs($this->admin)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'quick_action' => 'reject',
        ]);
        $rejectResponse->assertRedirect();

        $vendor->refresh();
        $this->assertEquals('rejected', $vendor->approval_status);
        $this->assertEquals('suspended', $vendor->status);

        // Quick suspend
        $suspendResponse = $this->actingAs($this->admin)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'quick_action' => 'suspend',
        ]);
        $suspendResponse->assertRedirect();
        $this->assertEquals('suspended', $vendor->refresh()->status);

        // Quick activate
        $activateResponse = $this->actingAs($this->admin)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'quick_action' => 'activate',
        ]);
        $activateResponse->assertRedirect();
        $this->assertEquals('active', $vendor->refresh()->status);
    }

    public function test_admin_can_update_vendor_commission_and_details(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Delta Pipes Co',
            'display_name' => 'Delta Pipes',
            'slug' => 'delta-pipes',
            'email' => 'sales@deltapipes.test',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 12.0,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 18.5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $vendor->refresh();
        $this->assertEquals(18.5, $vendor->commission_rate_percentage);
    }

    public function test_admin_can_view_payouts_ledger_with_kpis_and_filters(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Timber Corp',
            'display_name' => 'Timber World',
            'slug' => 'timber-world',
            'email' => 'support@timberworld.test',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 10.0,
        ]);

        $payout1 = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'payout_number' => 'PO-2026-001',
            'amount' => 5000000, // ₹50,000
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => 'UTR12345678',
        ]);

        $payout2 = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'payout_number' => 'PO-2026-002',
            'amount' => 2500000, // ₹25,000
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.marketplace.payouts'));
        $response->assertStatus(200);
        $response->assertSee('PO-2026-001');
        $response->assertSee('PO-2026-002');
        $response->assertSee('Timber World');

        // Check KPI views
        $response->assertSee('₹50,000.00'); // total disbursed
        $response->assertSee('₹25,000.00'); // pending settlement

        // Status filter: pending
        $pendingResp = $this->actingAs($this->admin)->get(route('admin.marketplace.payouts', ['status' => 'pending']));
        $pendingResp->assertStatus(200);
        $pendingResp->assertSee('PO-2026-002');
        $pendingResp->assertDontSee('PO-2026-001');

        // Vendor filter
        $vendorFilterResp = $this->actingAs($this->admin)->get(route('admin.marketplace.payouts', ['vendor_id' => $vendor->id]));
        $vendorFilterResp->assertStatus(200);
        $vendorFilterResp->assertSee('PO-2026-001');
    }

    public function test_admin_can_approve_and_settle_payout(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Granite & Marble Emporium',
            'display_name' => 'Granite World',
            'slug' => 'granite-world',
            'email' => 'sales@graniteworld.test',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 12.0,
        ]);

        $payout = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'payout_number' => 'PO-SETTLE-001',
            'amount' => 12000000, // ₹120,000
            'status' => 'pending',
        ]);

        // 1. Approve payout
        $approveResp = $this->actingAs($this->admin)->post(route('admin.marketplace.payouts.approve', $payout->id));
        $approveResp->assertRedirect();
        $approveResp->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('approved', $payout->status);

        // 2. Settle payout with bank UTR
        $settleResp = $this->actingAs($this->admin)->post(route('admin.marketplace.payouts.settle', $payout->id), [
            'payment_reference' => 'HDFC-NEFT-9988772211',
        ]);
        $settleResp->assertRedirect();
        $settleResp->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('paid', $payout->status);
        $this->assertEquals('HDFC-NEFT-9988772211', $payout->payment_reference);
        $this->assertNotNull($payout->paid_at);
    }
}
