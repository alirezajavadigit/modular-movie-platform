<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Http\Resources\Transformers\PermissionTransformer;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function index(): JsonResponse
    {
        $permissions = $this->permissionRepository->getAll();

        return ApiResponse::fractal(
            $permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.permissions.index'),
        );
    }

    public function byModule(string $modelName): JsonResponse
    {
        $permissions = $this->permissionRepository->findByModule($modelName);

        return ApiResponse::fractal(
            $permissions,
            new PermissionTransformer(),
            __('authorization-module::messages.permissions.show'),
        );
    }
}
