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

class UserQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/users/search',
        operationId: 'user.admin.search',
        summary: 'Search users by name, email, or phone',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/UserPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $term = (string) $request->input('q', '');
        $perPage = min((int) $request->input('per_page', config('user-module.per_page', 15)), 100);

        $users = $this->service->search($term, $perPage);

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.search'));
    }
}
