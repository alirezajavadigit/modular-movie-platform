<?php

declare(strict_types=1);

namespace Modules\Like\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Like\Contracts\LikeServiceInterface;
use Modules\Like\DTOs\CreateLikeDTO;
use Modules\Like\Http\Requests\StoreLikeRequest;
use Modules\Like\Http\Resources\Transformers\LikeTransformer;
use Modules\Like\Models\Like;

class LikeController extends Controller
{
    public function __construct(
        private readonly LikeServiceInterface $service,
        private readonly LikeTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', config('like-module.per_page', 15)), 100);
        $likes   = $this->service->getUserLikes($request->user()->id, $perPage);

        return ApiResponse::paginated(
            $likes,
            $this->transformer,
            __('like::messages.index'),
        );
    }

    public function store(StoreLikeRequest $request): JsonResponse
    {
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

    public function destroy(Like $like): JsonResponse
    {
        $this->service->delete($like);

        return ApiResponse::noContent(__('like::messages.deleted'));
    }

    public function toggle(StoreLikeRequest $request): JsonResponse
    {
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
