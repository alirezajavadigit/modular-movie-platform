<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;
use Modules\Authorization\Http\Resources\Transformers\PermissionTransformer;
use Modules\Authorization\Models\Permission;

class PermissionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

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
