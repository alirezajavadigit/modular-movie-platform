<?php

namespace Modules\Authorization\Tests\Unit;

use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Modules\Authorization\Contracts\RoleRepositoryInterface;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Services\RoleService;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Tests\TestCase;

final class RoleServiceTest extends TestCase
{
    private MockInterface $roleRepository;
    private RoleService $roleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->roleService = new RoleService($this->roleRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_roles_returns_collection(): void
    {
        $expectedCollection = new Collection();
        $this->roleRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($expectedCollection);

        $result = $this->roleService->getAllRoles();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_get_role_by_id_returns_role_when_found(): void
    {
        $expectedRole = new Role();
        $roleId = rand(1, 10);
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturn($expectedRole);

        $result = $this->roleService->getRoleById($roleId);
        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_get_role_by_id_throws_exception_when_not_found(): void
    {
        $roleId = rand(1, 10);
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturnNull();
        $this->expectException(ModelNotFoundException::class);
        $this->roleService->getRoleById($roleId);
    }

    public function test_create_role_returns_role_when_name_is_unique(): void
    {
        $dto = new CreateRoleDTO("role_1", "api", []);
        $expectedRole = new Role();

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with($dto->name)
            ->andReturnNull();

        $this->roleRepository
            ->shouldReceive('create')
            ->once()
            ->with($dto)
            ->andReturn($expectedRole);

        $result = $this->roleService->createRole($dto);
        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_create_role_throws_exception_when_name_already_exists(): void
    {
        $dto = new CreateRoleDTO("role_1", "api", []);
        $existingRole = new Role();

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with($dto->name)
            ->andReturn($existingRole);

        $this->expectException(RoleAlreadyExists::class);

        $this->roleService->createRole($dto);
    }

    public function test_update_role_returns_updated_role_when_found(): void
    {
        $dto = new UpdateRoleDTO("role_1", []);
        $roleId = rand(1, 10);
        $existingRole = new Role();

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturn($existingRole);
        $this->roleRepository
            ->shouldReceive('update')
            ->once()
            ->with($roleId, $dto)
            ->andReturn($existingRole);

        $result = $this->roleService->updateRole($roleId, $dto);
        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_update_role_throws_exception_when_not_found(): void
    {
        $dto = new UpdateRoleDTO("role_1", []);
        $roleId = rand(1, 10);
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturnNull();
        $this->expectException(ModelNotFoundException::class);
        $this->roleService->updateRole($roleId, $dto);
    }

    public function test_delete_role_returns_true_when_no_users_assigned(): void
    {
        $roleId = rand(1, 10);
        $existingRole = new Role();
        $existingRole->id = $roleId;


        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturn($existingRole);

        $this->roleRepository
            ->shouldReceive('hasUsersAssigned')
            ->once()
            ->andReturnFalse();

        $this->roleRepository
            ->shouldReceive('delete')
            ->once()
            ->with($roleId)
            ->andReturnTrue();

        $result = $this->roleService->deleteRole($roleId);
        $this->assertTrue($result);
    }


    public function test_delete_role_throws_exception_when_users_are_assigned(): void
    {
        $roleId = rand(1, 10);
        $existingRole = new Role();
        $existingRole->id = $roleId;


        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturn($existingRole);

        $this->roleRepository
            ->shouldReceive('hasUsersAssigned')
            ->once()
            ->andReturnTrue();

        $this->roleRepository
            ->shouldNotReceive('delete');

        $this->expectException(DomainException::class);
        $this->roleService->deleteRole($roleId);
    }


    public function test_delete_role_throws_exception_when_not_found(): void
    {
        $roleId = rand(1, 10);
        $existingRole = new Role();
        $existingRole->id = $roleId;


        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturnNull();

        $this->expectException(ModelNotFoundException::class);
        $this->roleService->deleteRole($roleId);
    }

    public function test_sync_permissions_returns_updated_role_when_found(): void
    {
        $roleId = rand(1, 10);
        $existingRole = new Role();
        $permissionNames = ["test1", "test2"];

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturn($existingRole);

        $this->roleRepository
            ->shouldReceive('syncPermissions')
            ->once()
            ->with($roleId, $permissionNames)
            ->andReturn($existingRole);

        $result = $this->roleService->syncPermissionsToRole($roleId, $permissionNames);
        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_sync_permissions_throws_exception_when_role_not_found(): void
    {
        $roleId = rand(1, 10);
        $permissionNames = ["test1", "test2"];

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($roleId)
            ->andReturnNull();

        $this->expectException(ModelNotFoundException::class);
        $this->roleService->syncPermissionsToRole($roleId, $permissionNames);
    }
}
