<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Http\Resources\Transformers\ArticleTransformer;
use Modules\Article\Models\Article;

class ArticleStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    public function publish(int $id): JsonResponse
    {
        $this->authorize('publish', Article::findOrFail($id));

        $article = $this->articleService->publish($id);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.published'),
        );
    }

    public function archive(int $id): JsonResponse
    {
        $this->authorize('archive', Article::findOrFail($id));

        $article = $this->articleService->archive($id);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.archived'),
        );
    }

    public function markAsDraft(int $id): JsonResponse
    {
        $this->authorize('markAsDraft', Article::findOrFail($id));

        $article = $this->articleService->markAsDraft($id);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.drafted'),
        );
    }
}
