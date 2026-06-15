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
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Http\Resources\Transformers\UserTransformer;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/users',
        operationId: 'user.admin.index',
        summary: 'List all users',
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
        $this->authorize('viewAny', User::class);

        $users = $this->service->paginate($this->perPage($request));

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.index'));
    }

    #[OA\Post(
        path: '/api/v1/admin/users',
        operationId: 'user.admin.store',
        summary: 'Create a user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreUserRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/UserCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $dto = new CreateUserDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            roles: $request->validated('roles', []),
        );

        $user = $this->service->store($dto);

        return ApiResponse::fractalCreated($user, $this->transformer, __('user::messages.created'));
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        operationId: 'user.admin.show',
        summary: 'Show a user',
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
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::fractal(
            $this->service->findById($user->id),
            $this->transformer,
            __('user::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        operationId: 'user.admin.update',
        summary: 'Update a user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateUserRequest'),
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/UserItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $dto = new UpdateUserDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            roles: $request->has('roles') ? $request->validated('roles', []) : null,
        );

        $updated = $this->service->update($user->id, $dto);

        return ApiResponse::fractal($updated, $this->transformer, __('user::messages.updated'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        operationId: 'user.admin.destroy',
        summary: 'Soft delete a user',
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
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->service->delete($user->id);

        return ApiResponse::noContent(__('user::messages.deleted'));
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('user-module.per_page', 15);

        return min((int) $request->input('per_page', $default), 100);
    }
}
