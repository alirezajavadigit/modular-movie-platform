<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\PermissionAssignmentServiceInterface;
use Modules\Authorization\Contracts\RoleAssignmentServiceInterface;
use Modules\Authorization\DTOs\AssignPermissionDTO;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;
use Modules\Authorization\DTOs\AssignRoleDTO;
use Modules\Authorization\DTOs\RevokePermissionDTO;
use Modules\Authorization\DTOs\RevokeRoleDTO;
use Modules\Authorization\DTOs\SyncRoleDTO;
use Modules\Authorization\Http\Requests\AssignPermissionRequest;
use Modules\Authorization\Http\Requests\AssignRoleRequest;
use Modules\Authorization\Http\Requests\RevokePermissionRequest;
use Modules\Authorization\Http\Requests\RevokeRoleRequest;
use Modules\Authorization\Http\Requests\SyncRoleRequest;
use Modules\Authorization\Http\Resources\Transformers\PermissionTransformer;
use Modules\Authorization\Http\Resources\Transformers\RoleTransformer;

class UserAuthorizationController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly RoleAssignmentServiceInterface $roleAssignmentService,
        private readonly PermissionAssignmentServiceInterface $permissionAssignmentService,
    ) {}

    public function assignRoles(AssignRoleRequest $request, int $userId): JsonResponse
    {
        $this->authorize('assignToUser', Role::class);

        $dto = new AssignRoleDTO(
            userId: $userId,
            roleNames: $request->validated('roles'),
        );

        $user = $this->roleAssignmentService->assignRoles($dto);

        return ApiResponse::fractal(
            $user->roles,
            new RoleTransformer(),
            __('authorization-module::messages.user_authorization.roles_assigned'),
        );
    }

    public function revokeRoles(RevokeRoleRequest $request, int $userId): JsonResponse
    {
        $this->authorize('revokeFromUser', Role::class);

        $dto = new RevokeRoleDTO(
            userId: $userId,
            roleNames: $request->validated('roles'),
        );

        $user = $this->roleAssignmentService->revokeRoles($dto);

        return ApiResponse::fractal(
            $user->roles,
            new RoleTransformer(),
            __('authorization-module::messages.user_authorization.roles_revoked'),
        );
    }

    public function syncRoles(SyncRoleRequest $request, int $userId): JsonResponse
    {
        $this->authorize('assignToUser', Role::class);

        $dto = new SyncRoleDTO(
            userId: $userId,
            roleNames: $request->validated('roles'),
        );

        $user = $this->roleAssignmentService->syncRoles($dto);
        return ApiResponse::fractal(
            $user->roles,
            new RoleTransformer(),
            __('authorization-module::messages.user_authorization.roles_synced'),
        );
    }

    public function assignPermissions(AssignPermissionRequest $request, int $userId): JsonResponse
    {
        $this->authorize('assignToUser', Permission::class);

        $dto = new AssignPermissionDTO(
            userId: $userId,
            permissionNames: $request->validated('permissions'),
        );

        $user = $this->permissionAssignmentService->givePermissions($dto);

        return ApiResponse::fractal(
            $user->permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.user_authorization.permissions_assigned'),
        );
    }

    public function revokePermissions(RevokePermissionRequest $request, int $userId): JsonResponse
    {
        $this->authorize('revokeFromUser', Permission::class);

        $dto = new RevokePermissionDTO(
            userId: $userId,
            permissionNames: $request->validated('permissions'),
        );

        $user = $this->permissionAssignmentService->revokePermissions($dto);

        return ApiResponse::fractal(
            $user->permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.user_authorization.permissions_revoked'),
        );
    }

    public function getUserRoles(int $userId): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleAssignmentService->getUserRoles($userId);

        return ApiResponse::fractal(
            $roles,
            new RoleTransformer(),
            __('authorization-module::messages.user_authorization.user_roles'),
        );
    }

    public function getUserPermissions(int $userId): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionAssignmentService->getUserPermissions($userId);

        return ApiResponse::fractal(
            $permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.user_authorization.user_permissions'),
        );
    }
}
