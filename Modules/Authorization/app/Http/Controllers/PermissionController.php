<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;
use Modules\Authorization\Http\Resources\Transformers\PermissionTransformer;
use Modules\Authorization\Models\Permission;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    #[OA\Get(
        path: '/api/v1/permissions',
        operationId: 'api.permissions.index',
        summary: 'List all permissions',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PermissionCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionRepository->getAll();

        return ApiResponse::fractal(
            $permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.permissions.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/permissions/module/{module}',
        operationId: 'api.permissions.module',
        summary: 'List permissions belonging to a module',
        security: [['bearerAuth' => []]],
        tags: ['Authorization'],
        parameters: [
            new OA\Parameter(name: 'module', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'movie'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PermissionCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byModule(string $modelName): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionRepository->findByModule($modelName);

        return ApiResponse::fractal(
            $permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.permissions.show'),
        );
    }
}
