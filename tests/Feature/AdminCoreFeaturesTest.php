<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCoreFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Tester',
            'email' => 'admin@universal-ecom.test',
        ]);
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->adminUser->roles()->attach($superAdminRole);
    }

    public function test_roles_index_displays_roles_and_permissions_matrix(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles.index'));
        $response->assertOk();
        $response->assertSee('Super Admin');
        $response->assertSee('Catalog Manager');
    }

    public function test_roles_create_screen_displays_permission_matrix_groups(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles.create'));
        $response->assertOk();
        $response->assertSee('Permission Matrix');
        $response->assertSee('Catalog');
        $response->assertSee('View Products');
        $response->assertSee('products.view');
    }

    public function test_role_can_be_created_with_permissions(): void
    {
        $perm = Permission::where('slug', 'products.view')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('admin.roles.store'), [
            'name' => 'Custom Reviewer',
            'slug' => 'custom-reviewer',
            'description' => 'Custom role for testing',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['slug' => 'custom-reviewer']);

        $role = Role::where('slug', 'custom-reviewer')->firstOrFail();
        $this->assertTrue($role->permissions->contains('id', $perm->id));
    }

    public function test_users_index_and_show_screen(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee($this->adminUser->name);

        $responseShow = $this->actingAs($this->adminUser)->get(route('admin.users.show', $this->adminUser->id));
        $responseShow->assertOk();
        $responseShow->assertSee('Assigned Roles');
        $responseShow->assertSee('Update Status');
    }

    public function test_catalog_category_and_tag_crud(): void
    {
        // Category Create
        $response = $this->actingAs($this->adminUser)->post(route('admin.catalog.categories.store'), [
            'name' => 'Structural Timber',
            'status' => 'active',
            'description' => 'Treated timber for framing',
        ]);
        $response->assertRedirect(route('admin.catalog.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Structural Timber']);

        // Tag Create
        $responseTag = $this->actingAs($this->adminUser)->post(route('admin.catalog.tags.store'), [
            'name' => 'Heavy Duty',
        ]);
        $responseTag->assertRedirect(route('admin.catalog.tags.index'));
        $this->assertDatabaseHas('tags', ['name' => 'Heavy Duty']);

        // Brand Create
        $responseBrand = $this->actingAs($this->adminUser)->post(route('admin.catalog.brands.store'), [
            'name' => 'Tata Steel',
            'status' => 'active',
        ]);
        $responseBrand->assertRedirect(route('admin.catalog.brands.index'));
        $this->assertDatabaseHas('brands', ['name' => 'Tata Steel']);
    }
}
