<?php

namespace Modules\Authorization\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\Http\Requests\SyncRolePermissionsRequest;
use Modules\Authorization\Http\Resources\Transformers\RoleTransformer;

class RolePermissionController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    public function sync(SyncRolePermissionsRequest $request, int $role): JsonResponse
    {
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
