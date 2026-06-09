<?php

namespace Modules\Authorization\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PermissionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_get_all_permissions_returns_200(): void
    {
        Permission::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/permissions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_get_all_permissions_returns_empty_when_none_exist(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/permissions');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_get_all_permissions_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/permissions');

        $response->assertUnauthorized();
    }

    public function test_get_permissions_by_module_returns_200(): void
    {
        Permission::factory()->create(['name' => 'movies.create']);
        Permission::factory()->create(['name' => 'movies.delete']);
        Permission::factory()->create(['name' => 'roles.view']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/permissions/module/movies');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_get_permissions_by_module_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/permissions/module/movies');

        $response->assertUnauthorized();
    }
}
