<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\Models\Role;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Http\Requests\StoreRoleRequest;
use Modules\Authorization\Http\Requests\UpdateRoleRequest;
use Modules\Authorization\Http\Resources\Transformers\RoleTransformer;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    #[OA\Get(
        path: '/api/v1/roles',
        operationId: 'api.roles.index',
        summary: 'List all roles',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->getAllRoles();

        return ApiResponse::fractal(
            $roles,
            new RoleTransformer(),
            __('authorization-module::messages.roles.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/roles',
        operationId: 'api.roles.store',
        summary: 'Create a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreRoleRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/RoleCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

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

    #[OA\Get(
        path: '/api/v1/roles/{role}',
        operationId: 'api.roles.show',
        summary: 'Show a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/RoleItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);
        $this->authorize('view', $role);

        return ApiResponse::fractal(
            $role,
            new RoleTransformer(),
            __('authorization-module::messages.roles.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/roles/{role}',
        operationId: 'api.roles.update',
        summary: 'Update a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateRoleRequest'),
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/RoleItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/roles/{role}',
        operationId: 'api.roles.patch',
        summary: 'Partially update a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateRoleRequest'),
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/RoleItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);

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

    #[OA\Delete(
        path: '/api/v1/roles/{role}',
        operationId: 'api.roles.destroy',
        summary: 'Delete a role',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);

        $this->roleService->deleteRole($id);

        return ApiResponse::noContent(
            __('authorization-module::messages.roles.destroy'),
        );
    }
}
