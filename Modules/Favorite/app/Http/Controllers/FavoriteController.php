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
use OpenApi\Attributes as OA;

class FavoriteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FavoriteServiceInterface $service,
        private readonly FavoriteTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/favorites',
        operationId: 'api.v1.favorites.index',
        summary: 'List the favorites of the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Favorite'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/FavoritePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/favorites',
        operationId: 'api.v1.favorites.store',
        summary: 'Favorite a resource',
        security: [['bearerAuth' => []]],
        tags: ['Favorite'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreFavoriteRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/FavoriteCreated')]
    #[OA\Response(response: 200, ref: '#/components/responses/FavoriteItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Delete(
        path: '/api/v1/favorites/{favorite}',
        operationId: 'api.v1.favorites.destroy',
        summary: 'Remove a favorite',
        security: [['bearerAuth' => []]],
        tags: ['Favorite'],
        parameters: [
            new OA\Parameter(name: 'favorite', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(Favorite $favorite): JsonResponse
    {
        $this->authorize('delete', $favorite);

        $this->service->delete($favorite);

        return ApiResponse::noContent(__('favorite::messages.deleted'));
    }

    #[OA\Post(
        path: '/api/v1/favorites/toggle',
        operationId: 'api.v1.favorites.toggle',
        summary: 'Toggle a favorite on or off',
        security: [['bearerAuth' => []]],
        tags: ['Favorite'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreFavoriteRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/FavoriteToggle')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
