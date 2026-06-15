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
use OpenApi\Attributes as OA;

class ArticleQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/articles/search',
        operationId: 'article.public.search',
        summary: 'Search articles',
        tags: ['Article'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/articles/published',
        operationId: 'article.public.published',
        summary: 'List published articles',
        tags: ['Article'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/admin/articles/drafts',
        operationId: 'article.admin.drafts',
        summary: 'List draft articles',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/admin/articles/archived',
        operationId: 'article.admin.archived',
        summary: 'List archived articles',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/admin/articles/status/{status}',
        operationId: 'article.admin.byStatus',
        summary: 'List articles by status',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/articles/author/{userId}',
        operationId: 'article.public.byAuthor',
        summary: 'List articles by author',
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/articles/{article}/related',
        operationId: 'article.public.related',
        summary: 'List articles related to an article',
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticleCollection')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function related(int $articleId): JsonResponse
    {
        $articles = $this->articleService->getRelated($articleId);

        return ApiResponse::fractalCollection(
            $articles,
            $this->articleTransformer,
            __('article::messages.related'),
        );
    }

    #[OA\Get(
        path: '/api/v1/articles/slug/{slug}',
        operationId: 'article.public.bySlug',
        summary: 'Show an article by slug',
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticleItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
