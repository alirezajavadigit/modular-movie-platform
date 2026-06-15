<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Http\Resources\Transformers\CategoryTransformer;
use Modules\Category\Models\Category;
use OpenApi\Attributes as OA;

class CategoryQueryController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/categories/active',
        operationId: 'category.public.active',
        summary: 'List active categories',
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CategoryPage'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Get(
        path: '/api/v1/admin/categories/active',
        operationId: 'category.admin.active',
        summary: 'List active categories',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CategoryPage'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function active(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->getActive($perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.active_list'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/categories/inactive',
        operationId: 'category.admin.inactive',
        summary: 'List inactive categories',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function inactive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->getInactive($perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.inactive_list'),
        );
    }

    #[OA\Get(
        path: '/api/v1/categories/tree',
        operationId: 'category.public.tree',
        summary: 'Get the full category tree',
        tags: ['Category'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryCollection')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function tree(): JsonResponse
    {
        $categories = $this->categoryService->getTree();

        return ApiResponse::fractalCollection(
            $categories,
            $this->categoryTransformer,
            __('category::messages.tree'),
        );
    }

    #[OA\Get(
        path: '/api/v1/categories/parent',
        operationId: 'category.public.byParentRoot',
        summary: 'List root categories',
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CategoryPage'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Get(
        path: '/api/v1/categories/parent/{parentId}',
        operationId: 'category.public.byParent',
        summary: 'List child categories of a parent',
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'parentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CategoryPage'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function byParent(Request $request, ?int $parentId = null): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->getByParent($parentId, $perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.by_parent'),
        );
    }

    #[OA\Get(
        path: '/api/v1/categories/search',
        operationId: 'category.public.search',
        summary: 'Search active categories',
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);

        $categories = $this->categoryService->search($query, $perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.search'),
        );
    }

    #[OA\Get(
        path: '/api/v1/categories/slug/{slug}',
        operationId: 'category.public.bySlug',
        summary: 'Show a category by slug',
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function findBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.show'),
        );
    }
}
