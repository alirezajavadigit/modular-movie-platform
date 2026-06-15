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
use OpenApi\Attributes as OA;

class TagQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/tags/active',
        operationId: 'tag.public.active',
        summary: 'List active tags',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/TagPage'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Get(
        path: '/api/v1/admin/tags/active',
        operationId: 'tag.admin.active',
        summary: 'List active tags',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/TagPage'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
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

    #[OA\Get(
        path: '/api/v1/admin/tags/inactive',
        operationId: 'tag.admin.inactive',
        summary: 'List inactive tags',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/tags/popular',
        operationId: 'tag.public.popular',
        summary: 'List the most used tags',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagCollection')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/tags/search',
        operationId: 'tag.public.search',
        summary: 'Search active tags',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/tags/slug/{slug}',
        operationId: 'tag.public.bySlug',
        summary: 'Show a tag by slug',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
