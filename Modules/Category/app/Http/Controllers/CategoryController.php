<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Models\Category;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Http\Requests\StoreCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Http\Resources\Transformers\CategoryTransformer;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/categories',
        operationId: 'category.admin.index',
        summary: 'List all categories with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, description: 'Integer ID, or "null" to list root categories', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->only(['q', 'is_active', 'parent_id', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);

        $categories = $this->categoryService->adminFilter($filters, $perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/categories',
        operationId: 'category.admin.store',
        summary: 'Create a category',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreCategoryRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/CategoryCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $validated = $request->validated();

        $dto = new CreateCategoryDTO(
            name: $validated['name'],
            slug: $validated['slug'],
            description: $validated['description'] ?? null,
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            isActive: (bool) ($validated['is_active'] ?? true),
            order: (int) ($validated['order'] ?? 0),
        );

        $category = $this->categoryService->store($dto);

        return ApiResponse::fractalCreated(
            $category,
            $this->categoryTransformer,
            __('category::messages.created'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/categories/{category}',
        operationId: 'category.admin.show',
        summary: 'Show a category',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        $this->authorize('view', $category);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/categories/{category}',
        operationId: 'category.admin.update',
        summary: 'Update a category',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateCategoryRequest'),
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CategoryItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Category::findOrFail($id));

        $validated = $request->validated();

        $dto = new UpdateCategoryDTO(
            name: $validated['name'] ?? null,
            slug: $validated['slug'] ?? null,
            description: array_key_exists('description', $validated) ? $validated['description'] : null,
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
            order: isset($validated['order']) ? (int) $validated['order'] : null,
        );

        $category = $this->categoryService->update($id, $dto);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.updated'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/categories/{category}',
        operationId: 'category.admin.destroy',
        summary: 'Soft delete a category',
        security: [['bearerAuth' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Category::findOrFail($id));

        $this->categoryService->delete($id);

        return ApiResponse::noContent(__('category::messages.deleted'));
    }
}
