<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\Http\Requests\SyncRolePermissionsRequest;
use Modules\Authorization\Http\Resources\Transformers\RoleTransformer;
use Modules\Authorization\Models\Role;
use OpenApi\Attributes as OA;

class RolePermissionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    #[OA\Put(
        path: '/api/v1/roles/{role}/permissions',
        operationId: 'api.roles.permissions.sync',
        summary: 'Replace the permission set of a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SyncRolePermissionsRequest'),
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function sync(SyncRolePermissionsRequest $request, int $role): JsonResponse
    {
        $roleModel = Role::findOrFail($role);
        $this->authorize('syncPermissions', $roleModel);

        $role = $this->roleService->syncPermissionsToRole(
            $role,
            $request->validated('permissions'),
        );

        return ApiResponse::fractal(
            $role,
            new RoleTransformer(),
            __('authorization-module::messages.roles.permissions_synced'),
        );
    }
}
