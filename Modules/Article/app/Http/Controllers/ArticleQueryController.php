<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Http\Resources\Transformers\ArticleTransformer;
use Modules\Article\Models\Article;

class ArticleQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);

        $articles = $this->articleService->search($query, $perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.search'),
        );
    }

    public function published(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getPublished($perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.published_list'),
        );
    }

    public function drafts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getDrafts($perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.drafts_list'),
        );
    }

    public function archived(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getArchived($perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.archived_list'),
        );
    }

    public function byStatus(Request $request, string $status): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getByStatus($status, $perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.by_status'),
        );
    }

    public function byAuthor(Request $request, int $userId): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getByAuthor($userId, $perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.by_author'),
        );
    }

    public function related(int $articleId): JsonResponse
    {
        $articles = $this->articleService->getRelated($articleId);

        return ApiResponse::fractalCollection(
            $articles,
            $this->articleTransformer,
            __('article::messages.related'),
        );
    }

    public function findBySlug(string $slug): JsonResponse
    {
        $article = $this->articleService->findBySlug($slug);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.show'),
        );
    }
}
