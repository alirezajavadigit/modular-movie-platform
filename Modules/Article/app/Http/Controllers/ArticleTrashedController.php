<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Http\Resources\Transformers\ArticleTransformer;

class ArticleTrashedController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getTrashed($perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.trashed'),
        );
    }

    public function restore(int $id): JsonResponse
    {
        $article = $this->articleService->restore($id);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.restored'),
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->articleService->forceDelete($id);

        return ApiResponse::noContent(__('article::messages.force_deleted'));
    }
}
