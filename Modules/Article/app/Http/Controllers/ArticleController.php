<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Models\Article;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Http\Requests\StoreArticleRequest;
use Modules\Article\Http\Requests\UpdateArticleRequest;
use Modules\Article\Http\Resources\Transformers\ArticleTransformer;
use OpenApi\Attributes as OA;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/articles',
        operationId: 'article.admin.index',
        summary: 'List all articles with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
            new OA\Parameter(name: 'author_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_featured', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticlePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        $filters = $request->only(['q', 'status', 'author_id', 'is_featured', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);

        $articles = $this->articleService->adminFilter($filters, $perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/articles',
        operationId: 'article.admin.store',
        summary: 'Create an article',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreArticleRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/ArticleCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $this->authorize('create', Article::class);

        $validated = $request->validated();

        $dto = new CreateArticleDTO(
            userId: (int) auth()->id(),
            title: $validated['title'],
            slug: $validated['slug'],
            summary: $validated['summary'] ?? null,
            body: $validated['body'],
            status: $validated['status'] ?? 'draft',
            readTime: isset($validated['read_time']) ? (int) $validated['read_time'] : null,
            isFeatured: (bool) ($validated['is_featured'] ?? false),
            allowComments: (bool) ($validated['allow_comments'] ?? true),
            publishedAt: $validated['published_at'] ?? null,
        );

        $article = $this->articleService->store($dto);

        if (!empty($validated['category_ids'])) {
            $this->articleRepository->syncCategories($article->id, $validated['category_ids']);
        }

        if (!empty($validated['tag_ids'])) {
            $this->articleRepository->syncTags($article->id, $validated['tag_ids']);
        }

        return ApiResponse::fractalCreated(
            $article->refresh(),
            $this->articleTransformer,
            __('article::messages.created'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/articles/{article}',
        operationId: 'article.admin.show',
        summary: 'Show an article',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticleItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $article = $this->articleService->findById($id);
        $this->authorize('view', $article);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/articles/{article}',
        operationId: 'article.admin.update',
        summary: 'Update an article',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateArticleRequest'),
        parameters: [
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/ArticleItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateArticleRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Article::findOrFail($id));

        $validated = $request->validated();

        $dto = new UpdateArticleDTO(
            title: $validated['title'] ?? null,
            slug: $validated['slug'] ?? null,
            summary: array_key_exists('summary', $validated) ? $validated['summary'] : null,
            body: $validated['body'] ?? null,
            status: $validated['status'] ?? null,
            readTime: isset($validated['read_time']) ? (int) $validated['read_time'] : null,
            isFeatured: isset($validated['is_featured']) ? (bool) $validated['is_featured'] : null,
            allowComments: isset($validated['allow_comments']) ? (bool) $validated['allow_comments'] : null,
            publishedAt: array_key_exists('published_at', $validated) ? $validated['published_at'] : null,
        );

        $article = $this->articleService->update($id, $dto);

        if (array_key_exists('category_ids', $validated)) {
            $this->articleRepository->syncCategories($article->id, $validated['category_ids']);
        }

        if (array_key_exists('tag_ids', $validated)) {
            $this->articleRepository->syncTags($article->id, $validated['tag_ids']);
        }

        return ApiResponse::fractal(
            $article->refresh(),
            $this->articleTransformer,
            __('article::messages.updated'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/articles/{article}',
        operationId: 'article.admin.destroy',
        summary: 'Soft delete an article',
        security: [['bearerAuth' => []]],
        tags: ['Article'],
        parameters: [
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Article::findOrFail($id));

        $this->articleService->delete($id);

        return ApiResponse::noContent(__('article::messages.deleted'));
    }
}
