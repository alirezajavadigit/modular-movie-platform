<?php

namespace Modules\Authorization\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class RoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'super-admin', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_get_all_roles_returns_200(): void
    {
        Role::factory()->count(2)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_get_all_roles_returns_only_seeded_roles_when_none_created(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_get_all_roles_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertUnauthorized();
    }

    public function test_get_role_by_id_returns_200(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => $role->name]);
    }

    public function test_get_role_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/roles/999');

        $response->assertNotFound();
    }

    public function test_get_role_by_id_requires_authentication(): void
    {
        $role = Role::factory()->create();

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }

    public function test_create_role_returns_201_without_permissions(): void
    {
        $payload = [
            'name'       => 'editor',
            'guard_name' => 'api',
            'permissions' => [],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/roles', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'editor']);

        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }

    public function test_create_role_returns_201_with_permissions(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);

        $payload = [
            'name'        => 'editor',
            'guard_name'  => 'api',
            'permissions' => ['movies.view', 'movies.edit'],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/roles', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'editor']);

        $role = Role::where('name', 'editor')->first();
        $this->assertTrue($role->hasPermissionTo('movies.view'));
        $this->assertTrue($role->hasPermissionTo('movies.edit'));
    }

    public function test_create_role_requires_name(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/roles', ['guard_name' => 'api']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_role_requires_unique_name(): void
    {
        Role::factory()->create(['name' => 'editor']);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/roles', ['name' => 'editor', 'guard_name' => 'api']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_role_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/roles', [
            'name'       => 'editor',
            'guard_name' => 'api',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_role_returns_200(): void
    {
        $role = Role::factory()->create(['name' => 'old-name']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}", [
                'name'        => 'new-name',
                'permissions' => [],
            ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'new-name']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-name']);
    }

    public function test_update_role_syncs_permissions(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);
        $role = Role::factory()->create(['name' => 'editor']);
        $role->syncPermissions(['movies.view']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}", [
                'name'        => 'editor',
                'permissions' => ['movies.edit'],
            ]);

        $response->assertOk();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('movies.edit'));
        $this->assertFalse($role->hasPermissionTo('movies.view'));
    }

    public function test_update_role_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->putJson('/api/v1/roles/999', ['name' => 'new-name', 'permissions' => []]);

        $response->assertNotFound();
    }

    public function test_update_role_requires_name(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}", ['permissions' => []]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_role_requires_unique_name(): void
    {
        Role::factory()->create(['name' => 'taken']);
        $role = Role::factory()->create(['name' => 'other']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}", ['name' => 'taken', 'permissions' => []]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_role_allows_same_name_on_same_record(): void
    {
        $role = Role::factory()->create(['name' => 'editor']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}", ['name' => 'editor', 'permissions' => []]);

        $response->assertOk();
    }

    public function test_update_role_requires_authentication(): void
    {
        $role = Role::factory()->create();

        $response = $this->putJson("/api/v1/roles/{$role->id}", [
            'name'        => 'new-name',
            'permissions' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_delete_role_returns_204(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_delete_role_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/roles/999');

        $response->assertNotFound();
    }

    public function test_delete_role_returns_422_when_users_are_assigned(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertUnprocessable();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_delete_role_requires_authentication(): void
    {
        $role = Role::factory()->create();

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }

    public function test_sync_permissions_returns_200(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);
        $role = Role::factory()->create();
        $role->syncPermissions(['movies.view']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}/permissions", [
                'permissions' => ['movies.edit'],
            ]);

        $response->assertOk();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('movies.edit'));
        $this->assertFalse($role->hasPermissionTo('movies.view'));
    }

    public function test_sync_permissions_with_empty_array_removes_all(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        $role = Role::factory()->create();
        $role->syncPermissions(['movies.view']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/roles/{$role->id}/permissions", [
                'permissions' => [],
            ]);

        $response->assertOk();

        $role->refresh();
        $this->assertCount(0, $role->permissions);
    }

    public function test_sync_permissions_returns_404_when_role_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->putJson('/api/v1/roles/999/permissions', ['permissions' => []]);

        $response->assertNotFound();
    }

    public function test_sync_permissions_requires_authentication(): void
    {
        $role = Role::factory()->create();

        $response = $this->putJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => [],
        ]);

        $response->assertUnauthorized();
    }
}
