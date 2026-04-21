<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Http\Requests\StoreRoleRequest;
use Modules\Authorization\Http\Requests\UpdateRoleRequest;
use Modules\Authorization\Http\Resources\Transformers\RoleTransformer;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return ApiResponse::fractal(
            $roles,
            new RoleTransformer(),
            __('authorization-module::messages.roles.index'),
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $dto = new CreateRoleDTO(
            name: $request->validated('name'),
            guardName: $request->validated('guard_name', 'api'),
            permissions: $request->validated('permissions', []),
        );

        $role = $this->roleService->createRole($dto);

        return ApiResponse::fractalCreated(
            $role,
            new RoleTransformer(),
            __('authorization-module::messages.roles.store'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);

        return ApiResponse::fractal(
            $role,
            new RoleTransformer(),
            __('authorization-module::messages.roles.show'),
        );
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $dto = new UpdateRoleDTO(
            name: $request->validated('name'),
            permissions: $request->validated('permissions'),
        );

        $role = $this->roleService->updateRole($id, $dto);

        return ApiResponse::fractal(
            $role,
            new RoleTransformer(),
            __('authorization-module::messages.roles.update'),
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->roleService->deleteRole($id);

        return ApiResponse::noContent(
            __('authorization-module::messages.roles.destroy'),
        );
    }


    
}
