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

class UserTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', User::class);

        $perPage = min((int) $request->input('per_page', config('user-module.per_page', 15)), 100);
        $users = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.trashed'));
    }

    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);

        $restored = $this->service->restore($user->id);

        return ApiResponse::fractal($restored, $this->transformer, __('user::messages.restored'));
    }

    public function forceDelete(User $user): JsonResponse
    {
        $this->authorize('forceDelete', $user);

        $this->service->forceDelete($user->id);

        return ApiResponse::noContent(__('user::messages.force_deleted'));
    }
}
