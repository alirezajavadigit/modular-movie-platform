<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Http\Requests\StoreCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Http\Resources\Transformers\CategoryTransformer;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->paginate($perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.index'),
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
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

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.show'),
        );
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
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

    public function destroy(int $id): JsonResponse
    {
        $this->categoryService->delete($id);

        return ApiResponse::noContent(__('category::messages.deleted'));
    }
}
