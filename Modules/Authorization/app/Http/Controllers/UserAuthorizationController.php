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
use OpenApi\Attributes as OA;

class UserAuthorizationController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly RoleAssignmentServiceInterface $roleAssignmentService,
        private readonly PermissionAssignmentServiceInterface $permissionAssignmentService,
    ) {}

    #[OA\Post(
        path: '/api/v1/users/{userId}/roles/assign',
        operationId: 'api.users.roles.assign',
        summary: 'Assign roles to a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/RoleNamesRequest'),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/users/{userId}/roles/revoke',
        operationId: 'api.users.roles.revoke',
        summary: 'Revoke roles from a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/RoleNamesRequest'),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/users/{userId}/roles/sync',
        operationId: 'api.users.roles.sync',
        summary: 'Replace the role set of a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SyncRolesRequest'),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/users/{userId}/permissions/assign',
        operationId: 'api.users.permissions.assign',
        summary: 'Grant direct permissions to a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/PermissionNamesRequest'),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PermissionCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/users/{userId}/permissions/revoke',
        operationId: 'api.users.permissions.revoke',
        summary: 'Revoke direct permissions from a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/PermissionNamesRequest'),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PermissionCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/users/{userId}/roles',
        operationId: 'api.users.roles.index',
        summary: 'List the roles of a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/users/{userId}/permissions',
        operationId: 'api.users.permissions.index',
        summary: 'List the direct permissions of a user',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PermissionCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
