<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->admin = User::factory()->create([
            'name' => 'Security Admin',
            'email' => 'audit.admin@universal-ecom.test',
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    public function test_guest_and_non_admin_cannot_access_audit_log(): void
    {
        $guestResp = $this->get(route('admin.audit'));
        $guestResp->assertRedirect(route('admin.login'));

        $customer = User::factory()->create();
        $customerResp = $this->actingAs($customer)->get(route('admin.audit'));
        $customerResp->assertStatus(403);
    }

    public function test_admin_can_view_audit_logs_and_filter_by_keyword_action_and_entity(): void
    {
        $operator = User::factory()->create([
            'name' => 'Vikram Warehouse Supervisor',
            'email' => 'vikram@depot.test',
        ]);

        AuditLog::create([
            'user_id' => $operator->id,
            'action' => 'stock.adjusted',
            'entity_type' => 'App\Models\InventoryItem',
            'entity_id' => 101,
            'ip_address' => '192.168.1.55',
            'changes' => ['quantity' => 50],
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'order.refunded',
            'entity_type' => 'App\Models\Order',
            'entity_id' => 505,
            'ip_address' => '10.0.0.1',
            'changes' => ['amount' => 15000],
        ]);

        // 1. Visit audit logs page
        $response = $this->actingAs($this->admin)->get(route('admin.audit'));
        $response->assertOk();
        $response->assertSee('Security Audit Log');
        $response->assertSee('stock.adjusted');
        $response->assertSee('order.refunded');
        $response->assertSee('Vikram Warehouse Supervisor');
        $response->assertSee('Total Audit Events');
        $response->assertSee("Today's Activity", false);

        // 2. Search by operator name / keyword 'vikram'
        $searchResp = $this->actingAs($this->admin)->get(route('admin.audit', ['q' => 'vikram']));
        $searchResp->assertOk();
        $searchResp->assertSee('vikram@depot.test');
        $searchResp->assertSee('#101');
        $searchResp->assertDontSee('#505');

        // 3. Search by IP '10.0.0.1'
        $ipResp = $this->actingAs($this->admin)->get(route('admin.audit', ['q' => '10.0.0.1']));
        $ipResp->assertOk();
        $ipResp->assertSee('#505');
        $ipResp->assertDontSee('#101');

        // 4. Filter by Action 'order.refunded'
        $actionResp = $this->actingAs($this->admin)->get(route('admin.audit', ['action' => 'order.refunded']));
        $actionResp->assertOk();
        $actionResp->assertSee('#505');
        $actionResp->assertDontSee('#101');
    }
}
