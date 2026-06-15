<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\Http\Resources\Transformers\UserTransformer;
use OpenApi\Attributes as OA;

class UserTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/users/trashed',
        operationId: 'user.admin.trashed',
        summary: 'List soft-deleted users',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/UserPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', User::class);

        $perPage = min((int) $request->input('per_page', config('user-module.per_page', 15)), 100);
        $users = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/users/{user}/restore',
        operationId: 'user.admin.restore',
        summary: 'Restore a soft-deleted user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/UserItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);

        $restored = $this->service->restore($user->id);

        return ApiResponse::fractal($restored, $this->transformer, __('user::messages.restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}/force-delete',
        operationId: 'user.admin.forceDelete',
        summary: 'Permanently delete a user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(User $user): JsonResponse
    {
        $this->authorize('forceDelete', $user);

        $this->service->forceDelete($user->id);

        return ApiResponse::noContent(__('user::messages.force_deleted'));
    }
}
