<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Http\Resources\Transformers\CategoryTransformer;

class CategoryTrashedController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->getTrashed($perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.trashed'),
        );
    }

    public function restore(int $id): JsonResponse
    {
        $category = $this->categoryService->restore($id);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.restored'),
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->categoryService->forceDelete($id);

        return ApiResponse::noContent(__('category::messages.force_deleted'));
    }
}
