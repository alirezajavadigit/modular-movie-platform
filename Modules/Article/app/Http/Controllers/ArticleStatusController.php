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
use OpenApi\Attributes as OA;

class ArticleStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticleTransformer $articleTransformer,
    ) {}

    #[OA\Patch(
        path: '/api/v1/admin/articles/{article}/publish',
        operationId: 'article.admin.publish',
        summary: 'Publish an article',
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

    #[OA\Patch(
        path: '/api/v1/admin/articles/{article}/archive',
        operationId: 'article.admin.archive',
        summary: 'Archive an article',
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

    #[OA\Patch(
        path: '/api/v1/admin/articles/{article}/draft',
        operationId: 'article.admin.markAsDraft',
        summary: 'Move an article back to draft',
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
