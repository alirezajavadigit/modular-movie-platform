<?php

declare(strict_types=1);

namespace Modules\Tag\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\Http\Resources\Transformers\TagTransformer;
use Modules\Tag\Models\Tag;

class TagQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    public function active(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $tags = $this->tagService->getActive($perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.active_list'),
        );
    }

    public function inactive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);
        $perPage = (int) $request->input('per_page', 15);
        
        $tags = $this->tagService->getInactive($perPage);
        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.inactive_list'),
        );
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $tags = $this->tagService->getPopular($limit);

        return ApiResponse::fractalCollection(
            $tags,
            $this->tagTransformer,
            __('tag::messages.popular'),
        );
    }

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);

        $tags = $this->tagService->search($query, $perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.search'),
        );
    }

    public function findBySlug(string $slug): JsonResponse
    {
        $tag = $this->tagService->findBySlug($slug);

        return ApiResponse::fractal(
            $tag,
            $this->tagTransformer,
            __('tag::messages.show'),
        );
    }
}
