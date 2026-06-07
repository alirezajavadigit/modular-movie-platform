<?php

declare(strict_types=1);

namespace Modules\Favorite\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Favorite\Contracts\FavoriteServiceInterface;
use Modules\Favorite\DTOs\CreateFavoriteDTO;
use Modules\Favorite\Http\Requests\StoreFavoriteRequest;
use Modules\Favorite\Http\Resources\Transformers\FavoriteTransformer;
use Modules\Favorite\Models\Favorite;

class FavoriteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FavoriteServiceInterface $service,
        private readonly FavoriteTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Favorite::class);

        $perPage   = min((int) $request->input('per_page', config('favorite.per_page', 15)), 100);
        $favorites = $this->service->getUserFavorites($request->user()->id, $perPage);

        return ApiResponse::paginated(
            $favorites,
            $this->transformer,
            __('favorite-module::messages.index'),
        );
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $this->authorize('create', Favorite::class);

        $dto = CreateFavoriteDTO::fromRequest(
            userId: $request->user()->id,
            favoriteableId: $request->integer('favoriteable_id'),
            favoriteableType: $request->resolvedType(),
        );

        $existing = $this->service->findExisting(
            $dto->userId,
            $dto->favoriteableType,
            $dto->favoriteableId,
        );

        if ($existing) {
            return ApiResponse::fractal(
                $existing,
                $this->transformer,
                __('favorite::messages.exists'),
            );
        }

        $favorite = $this->service->store($dto);

        return ApiResponse::fractalCreated(
            $favorite,
            $this->transformer,
            __('favorite::messages.created'),
        );
    }

    public function destroy(Favorite $favorite): JsonResponse
    {
        $this->authorize('delete', $favorite);

        $this->service->delete($favorite);

        return ApiResponse::noContent(__('favorite::messages.deleted'));
    }

    public function toggle(StoreFavoriteRequest $request): JsonResponse
    {
        $this->authorize('create', Favorite::class);

        $dto = CreateFavoriteDTO::fromRequest(
            userId: $request->user()->id,
            favoriteableId: $request->integer('favoriteable_id'),
            favoriteableType: $request->resolvedType(),
        );

        $result  = $this->service->toggle($dto);
        $message = $result['favorited']
            ? __('favorite::messages.toggled_on')
            : __('favorite::messages.toggled_off');

        return ApiResponse::success($result, $message);
    }
}
