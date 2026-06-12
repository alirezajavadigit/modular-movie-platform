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

class CategoryTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/categories/trashed',
        operationId: 'category.admin.trashed',
        summary: 'List soft-deleted categories',
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Category::class);

        $perPage = (int) $request->input('per_page', 15);
        $categories = $this->categoryService->getTrashed($perPage);

        return ApiResponse::paginated(
            $categories,
            $this->categoryTransformer,
            __('category::messages.trashed'),
        );
    }

    #[OA\Patch(
        path: '/api/v1/admin/categories/{category}/restore',
        operationId: 'category.admin.restore',
        summary: 'Restore a soft-deleted category',
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
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Category::withTrashed()->findOrFail($id));

        $category = $this->categoryService->restore($id);

        return ApiResponse::fractal(
            $category,
            $this->categoryTransformer,
            __('category::messages.restored'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/categories/{category}/force-delete',
        operationId: 'category.admin.forceDelete',
        summary: 'Permanently delete a category',
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
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Category::withTrashed()->findOrFail($id));

        $this->categoryService->forceDelete($id);

        return ApiResponse::noContent(__('category::messages.force_deleted'));
    }
}
