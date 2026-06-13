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

class TagTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/tags/trashed',
        operationId: 'tag.admin.trashed',
        summary: 'List soft-deleted tags',
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Tag::class);

        $perPage = (int) $request->input('per_page', 15);
        $tags = $this->tagService->getTrashed($perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.trashed'),
        );
    }

    #[OA\Patch(
        path: '/api/v1/admin/tags/{tag}/restore',
        operationId: 'tag.admin.restore',
        summary: 'Restore a soft-deleted tag',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Tag::withTrashed()->findOrFail($id));

        $tag = $this->tagService->restore($id);

        return ApiResponse::fractal(
            $tag,
            $this->tagTransformer,
            __('tag::messages.restored'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/tags/{tag}/force-delete',
        operationId: 'tag.admin.forceDelete',
        summary: 'Permanently delete a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Tag::withTrashed()->findOrFail($id));

        $this->tagService->forceDelete($id);

        return ApiResponse::noContent(__('tag::messages.force_deleted'));
    }
}
