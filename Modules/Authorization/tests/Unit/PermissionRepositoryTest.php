<?php

namespace Modules\Authorization\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Repositories\PermissionRepository;
use Tests\TestCase;

final class PermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PermissionRepository(new Permission());
    }

    public function test_get_all_returns_empty_collection_when_no_permissions_exist(): void
    {
        $result = $this->repository->getAll();

        $this->assertEmpty($result);
    }

    public function test_get_all_returns_all_permissions(): void
    {
        Permission::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_find_by_id_returns_permission_when_exists(): void
    {
        $permission = Permission::factory()->create();

        $result = $this->repository->findById($permission->id);

        $this->assertNotNull($result);
        $this->assertEquals($permission->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_find_by_name_returns_permission_when_exists(): void
    {
        $permission = Permission::factory()->create(['name' => 'users.create']);

        $result = $this->repository->findByName('users.create');

        $this->assertNotNull($result);
        $this->assertEquals('users.create', $result->name);
    }

    public function test_find_by_name_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByName('nonexistent.permission');

        $this->assertNull($result);
    }

    public function test_find_by_names_returns_matching_permissions(): void
    {
        Permission::factory()->create(['name' => 'users.create']);
        Permission::factory()->create(['name' => 'users.delete']);
        Permission::factory()->create(['name' => 'posts.create']);

        $result = $this->repository->findByNames(['users.create', 'users.delete']);

        $this->assertCount(2, $result);
        $this->assertTrue($result->pluck('name')->contains('users.create'));
        $this->assertTrue($result->pluck('name')->contains('users.delete'));
    }

    public function test_find_by_names_returns_empty_collection_when_none_match(): void
    {
        Permission::factory()->create(['name' => 'users.create']);

        $result = $this->repository->findByNames(['nonexistent.one', 'nonexistent.two']);

        $this->assertEmpty($result);
    }

    public function test_find_by_names_returns_empty_collection_when_given_empty_array(): void
    {
        Permission::factory()->create(['name' => 'users.create']);

        $result = $this->repository->findByNames([]);

        $this->assertEmpty($result);
    }

    public function test_find_by_module_returns_permissions_with_matching_prefix(): void
    {
        Permission::factory()->create(['name' => 'users.create']);
        Permission::factory()->create(['name' => 'users.update']);
        Permission::factory()->create(['name' => 'users.delete']);
        Permission::factory()->create(['name' => 'posts.create']);

        $result = $this->repository->findByModule('users');

        $this->assertCount(3, $result);
        $result->each(fn($p) => $this->assertStringStartsWith('users.', $p->name));
    }

    public function test_find_by_module_returns_empty_collection_when_no_match(): void
    {
        Permission::factory()->create(['name' => 'users.create']);

        $result = $this->repository->findByModule('orders');

        $this->assertEmpty($result);
    }

    public function test_find_by_module_does_not_return_partial_prefix_matches(): void
    {
        Permission::factory()->create(['name' => 'users.create']);
        Permission::factory()->create(['name' => 'users_admin.create']);

        $result = $this->repository->findByModule('users');

        $this->assertCount(1, $result);
        $this->assertEquals('users.create', $result->first()->name);
    }
}
