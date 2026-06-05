<?php

declare(strict_types=1);

namespace Modules\Tag\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\Http\Resources\Transformers\TagTransformer;
use Modules\Tag\Models\Tag;

class TagTrashedController extends Controller
{
    protected static string $modelClass = Tag::class;
    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $tags = $this->tagService->getTrashed($perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.trashed'),
        );
    }

    public function restore(int $id): JsonResponse
    {
        $tag = $this->tagService->restore($id);

        return ApiResponse::fractal(
            $tag,
            $this->tagTransformer,
            __('tag::messages.restored'),
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->tagService->forceDelete($id);

        return ApiResponse::noContent(__('tag::messages.force_deleted'));
    }
}
