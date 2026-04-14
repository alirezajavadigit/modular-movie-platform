<?php

namespace Modules\Authorization\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Repositories\RoleRepository;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->repository = new RoleRepository(new Role());
    }

    public function test_get_all_roles_returns_collection(): void
    {
        Role::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_get_all_roles_returns_empty_collection_when_no_roles(): void
    {
        $result = $this->repository->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_find_by_id_returns_role_when_found(): void
    {
        $role = Role::factory()->create(['name' => 'role-1']);

        $result = $this->repository->findById($role->id);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals($role->id, $result->id);
        $this->assertEquals('role-1', $result->name);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_find_by_name_returns_role_when_found(): void
    {
        Role::factory()->create(['name' => 'admin']);

        $result = $this->repository->findByName('admin');

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals('admin', $result->name);
    }

    public function test_find_by_name_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByName('something_not_exists');

        $this->assertNull($result);
    }

    public function test_find_by_names_returns_matching_roles(): void
    {
        Role::factory()->create(['name' => 'role-1']);
        Role::factory()->create(['name' => 'role-2']);
        Role::factory()->create(['name' => 'role-3']);

        $result = $this->repository->findByNames(['role-1', 'role-3']);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_find_by_names_returns_empty_collection_when_not_found(): void
    {
        $result = $this->repository->findByNames(['not_exists_1', 'not_exists_2']);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_create_returns_role_without_permissions(): void
    {
        $dto = new CreateRoleDTO('editor', 'api', []);

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals('editor', $result->name);
        $this->assertEquals('api', $result->guard_name);
        $this->assertDatabaseHas('roles', ['name' => 'editor', 'guard_name' => 'api']);
    }

    public function test_create_returns_role_with_permissions(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);

        $dto = new CreateRoleDTO('editor', 'api', ['movies.view', 'movies.edit']);

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals('editor', $result->name);
        $this->assertTrue($result->hasPermissionTo('movies.view'));
        $this->assertTrue($result->hasPermissionTo('movies.edit'));
    }

    public function test_update_returns_updated_role(): void
    {
        $role = Role::factory()->create(['name' => 'old-name']);

        $dto = new UpdateRoleDTO('new-name', []);

        $result = $this->repository->update($role->id, $dto);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals('new-name', $result->name);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-name']);
    }

    public function test_update_syncs_permissions(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);
        $role = Role::factory()->create(['name' => 'editor']);
        $role->syncPermissions(['movies.view']);

        $dto = new UpdateRoleDTO('editor', ['movies.edit']);

        $result = $this->repository->update($role->id, $dto);

        $this->assertTrue($result->hasPermissionTo('movies.edit'));
        $this->assertFalse($result->hasPermissionTo('movies.view'));
    }

    public function test_update_throws_exception_when_role_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->update(999, new UpdateRoleDTO('new-name', []));
    }

    public function test_delete_returns_true_when_role_exists(): void
    {
        $role = Role::factory()->create(['name' => 'to-delete']);

        $result = $this->repository->delete($role->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_delete_throws_exception_when_role_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999);
    }

    public function test_sync_permissions_replaces_existing_permissions(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        Permission::factory()->create(['name' => 'movies.edit']);
        Permission::factory()->create(['name' => 'movies.delete']);
        $role = Role::factory()->create(['name' => 'editor']);
        $role->syncPermissions(['movies.view', 'movies.edit']);

        $result = $this->repository->syncPermissions($role->id, ['movies.delete']);

        $this->assertTrue($result->hasPermissionTo('movies.delete'));
        $this->assertFalse($result->hasPermissionTo('movies.view'));
        $this->assertFalse($result->hasPermissionTo('movies.edit'));
    }

    public function test_sync_permissions_with_empty_array_removes_all(): void
    {
        Permission::factory()->create(['name' => 'movies.view']);
        $role = Role::factory()->create(['name' => 'editor']);
        $role->syncPermissions(['movies.view']);

        $result = $this->repository->syncPermissions($role->id, []);

        $this->assertCount(0, $result->permissions);
    }

    public function test_sync_permissions_throws_exception_when_role_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->syncPermissions(999, ['movies.view']);
    }

    public function test_has_users_assigned_returns_true_when_users_exist(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $result = $this->repository->hasUsersAssigned($role->id);

        $this->assertTrue($result);
    }

    public function test_has_users_assigned_returns_false_when_no_users(): void
    {
        $role = Role::factory()->create(['name' => 'empty-role']);

        $result = $this->repository->hasUsersAssigned($role->id);

        $this->assertFalse($result);
    }

    public function test_has_users_assigned_throws_exception_when_role_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->hasUsersAssigned(999);
    }
}
