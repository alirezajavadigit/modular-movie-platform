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

class TagController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $tagTransformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        $perPage = (int) $request->input('per_page', 15);
        $query =  $request->input('q', '');
        $tags = $query ? $this->tagService->searchAll($query, $perPage) : $this->tagService->paginate($perPage);

        return ApiResponse::paginated(
            $tags,
            $this->tagTransformer,
            __('tag::messages.index'),
        );
    }

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

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Tag::findOrFail($id));

        $this->tagService->delete($id);

        return ApiResponse::noContent(__('tag::messages.deleted'));
    }
}
