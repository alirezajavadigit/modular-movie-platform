<?php

declare(strict_types=1);

namespace Modules\Like\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Like\Contracts\LikeServiceInterface;
use Modules\Like\DTOs\CreateLikeDTO;
use Modules\Like\Http\Requests\StoreLikeRequest;
use Modules\Like\Http\Resources\Transformers\LikeTransformer;
use Modules\Like\Models\Like;
use OpenApi\Attributes as OA;

class LikeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LikeServiceInterface $service,
        private readonly LikeTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/likes',
        operationId: 'api.v1.likes.index',
        summary: 'List the likes of the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Like'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/LikePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Like::class);

        $perPage = min((int) $request->input('per_page', config('like-module.per_page', 15)), 100);
        $likes   = $this->service->getUserLikes($request->user()->id, $perPage);

        return ApiResponse::paginated(
            $likes,
            $this->transformer,
            __('like::messages.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/likes',
        operationId: 'api.v1.likes.store',
        summary: 'Like a resource',
        security: [['bearerAuth' => []]],
        tags: ['Like'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreLikeRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/LikeCreated')]
    #[OA\Response(response: 200, ref: '#/components/responses/LikeItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreLikeRequest $request): JsonResponse
    {
        $this->authorize('create', Like::class);

        $dto = new CreateLikeDTO(
            userId: $request->user()->id,
            likeableId: $request->integer('likeable_id'),
            likeableType: $request->resolvedType(),
        );

        $existing = $this->service->findExisting(
            $dto->userId,
            $dto->likeableType,
            $dto->likeableId,
        );

        if ($existing) {
            return ApiResponse::fractal(
                $existing,
                $this->transformer,
                __('like::messages.exists'),
            );
        }

        $like = $this->service->store($dto);

        return ApiResponse::fractalCreated(
            $like,
            $this->transformer,
            __('like::messages.created'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/likes/{like}',
        operationId: 'api.v1.likes.destroy',
        summary: 'Remove a like',
        security: [['bearerAuth' => []]],
        tags: ['Like'],
        parameters: [
            new OA\Parameter(name: 'like', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(Like $like): JsonResponse
    {
        $this->authorize('delete', $like);

        $this->service->delete($like);

        return ApiResponse::noContent(__('like::messages.deleted'));
    }

    #[OA\Post(
        path: '/api/v1/likes/toggle',
        operationId: 'api.v1.likes.toggle',
        summary: 'Toggle a like on or off',
        security: [['bearerAuth' => []]],
        tags: ['Like'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreLikeRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/LikeToggle')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function toggle(StoreLikeRequest $request): JsonResponse
    {
        $this->authorize('create', Like::class);

        $dto = new CreateLikeDTO(
            userId: $request->user()->id,
            likeableId: $request->integer('likeable_id'),
            likeableType: $request->resolvedType(),
        );

        $result  = $this->service->toggle($dto);
        $message = $result['liked']
            ? __('like::messages.toggled_on')
            : __('like::messages.toggled_off');

        return ApiResponse::success($result, $message);
    }
}
