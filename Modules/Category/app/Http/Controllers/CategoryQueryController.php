<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Http\Resources\Transformers\CategoryTransformer;

class CategoryQueryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

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

    public function tree(): JsonResponse
    {
        $categories = $this->categoryService->getTree();

        return ApiResponse::fractalCollection(
            $categories,
            $this->categoryTransformer,
            __('category::messages.tree'),
        );
    }

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
