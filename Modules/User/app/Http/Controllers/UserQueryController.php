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

class UserQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserServiceInterface $service,
        private readonly UserTransformer $transformer,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $term = (string) $request->input('q', '');
        $perPage = min((int) $request->input('per_page', config('user-module.per_page', 15)), 100);

        $users = $this->service->search($term, $perPage);

        return ApiResponse::paginated($users, $this->transformer, __('user::messages.search'));
    }
}
