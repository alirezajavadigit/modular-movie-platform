<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Http\Requests\StoreArticleRequest;
use Modules\Article\Http\Requests\UpdateArticleRequest;
use Modules\Article\Http\Resources\Transformers\ArticleTransformer;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->paginate($perPage);
        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.index'),
        );
    }
    public function store(StoreArticleRequest $request): JsonResponse
    {
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
        return ApiResponse::fractalCreated(
            $article,
            $this->articleTransformer,
            __('article::messages.created'),
        );
    }
    public function show(int $id): JsonResponse
    {
        $article = $this->articleService->findById($id);
        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.show'),
        );
    }
    public function update(UpdateArticleRequest $request, int $id): JsonResponse
    {
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
        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.updated'),
        );
    }
    public function destroy(int $id): JsonResponse
    {
        $this->articleService->delete($id);
        return ApiResponse::noContent(__('article::messages.deleted'));
    }
}
