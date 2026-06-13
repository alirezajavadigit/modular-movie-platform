<?php

declare(strict_types=1);

namespace Modules\Tag\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\Models\Tag;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Http\Requests\StoreTagRequest;
use Modules\Tag\Http\Requests\UpdateTagRequest;
use Modules\Tag\Http\Resources\Transformers\TagTransformer;
use OpenApi\Attributes as OA;

class TagController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/tags',
        operationId: 'tag.admin.index',
        summary: 'List all tags with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
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
        $this->authorize('viewAny', Tag::class);

        $filters = $request->only(['q', 'is_active', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);

        $tags = $this->tagService->adminFilter($filters, $perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/tags',
        operationId: 'tag.admin.store',
        summary: 'Create a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreTagRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/TagCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $validated = $request->validated();

        $dto = new CreateTagDTO(
            name: $validated['name'],
            slug: $validated['slug'],
            description: $validated['description'] ?? null,
            color: $validated['color'] ?? null,
            isActive: (bool) ($validated['is_active'] ?? true),
        );

        $tag = $this->tagService->store($dto);

        return ApiResponse::fractalCreated(
            $tag,
            $this->tagTransformer,
            __('tag::messages.created'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/tags/{tag}',
        operationId: 'tag.admin.show',
        summary: 'Show a tag',
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
    public function show(int $id): JsonResponse
    {
        $tag = $this->tagService->findById($id);
        $this->authorize('view', $tag);

        return ApiResponse::fractal(
            $tag,
            $this->tagTransformer,
            __('tag::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/tags/{tag}',
        operationId: 'tag.admin.update',
        summary: 'Update a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateTagRequest'),
        parameters: [
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/TagItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Tag::findOrFail($id));

        $validated = $request->validated();

        $dto = new UpdateTagDTO(
            name: $validated['name'] ?? null,
            slug: $validated['slug'] ?? null,
            description: array_key_exists('description', $validated) ? $validated['description'] : null,
            color: $validated['color'] ?? null,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
        );

        $tag = $this->tagService->update($id, $dto);

        return ApiResponse::fractal(
            $tag,
            $this->tagTransformer,
            __('tag::messages.updated'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/tags/{tag}',
        operationId: 'tag.admin.destroy',
        summary: 'Soft delete a tag',
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
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Tag::findOrFail($id));

        $this->tagService->delete($id);

        return ApiResponse::noContent(__('tag::messages.deleted'));
    }
}
