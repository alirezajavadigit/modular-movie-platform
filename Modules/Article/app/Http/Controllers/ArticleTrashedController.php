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

class ArticleTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/articles/trashed',
        operationId: 'article.admin.trashed',
        summary: 'List soft-deleted articles',
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Article::class);

        $perPage = (int) $request->input('per_page', 15);
        $articles = $this->articleService->getTrashed($perPage);

        return ApiResponse::paginated(
            $articles,
            $this->articleTransformer,
            __('article::messages.trashed'),
        );
    }

    #[OA\Patch(
        path: '/api/v1/admin/articles/{article}/restore',
        operationId: 'article.admin.restore',
        summary: 'Restore a soft-deleted article',
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
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Article::withTrashed()->findOrFail($id));

        $article = $this->articleService->restore($id);

        return ApiResponse::fractal(
            $article,
            $this->articleTransformer,
            __('article::messages.restored'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/articles/{article}/force-delete',
        operationId: 'article.admin.forceDelete',
        summary: 'Permanently delete an article',
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
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Article::withTrashed()->findOrFail($id));

        $this->articleService->forceDelete($id);

        return ApiResponse::noContent(__('article::messages.force_deleted'));
    }
}
