<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Http\Resources\Transformers\UserTransformer;

class UserController extends Controller
{
    public static string $modelClass = \Modules\Auth\Models\User::class;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->service->paginate($this->perPage($request));

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.index'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
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

    public function show(User $user): JsonResponse
    {
        return ApiResponse::fractal(
            $this->service->findById($user->id),
            $this->transformer,
            __('user::messages.show'),
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
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

    public function destroy(User $user): JsonResponse
    {
        $this->service->delete($user->id);

        return ApiResponse::noContent(__('user::messages.deleted'));
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('user-module.per_page', 15);

        return min((int) $request->input('per_page', $default), 100);
    }
}
