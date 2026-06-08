<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Http\Requests\StoreDiscussionRequest;
use Modules\Discussion\Http\Requests\UpdateDiscussionRequest;
use Modules\Discussion\Http\Resources\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    public function store(StoreDiscussionRequest $request): JsonResponse
    {
        $this->authorize('create', Discussion::class);

        $autoApprove = (bool) config('discussion-module.auto_approve', false);

        $dto = new CreateDiscussionDTO(
            userId: (int) $request->user()->getKey(),
            discussionableId: (int) $request->input('discussionable_id'),
            discussionableType: $request->getMorphClass(),
            body: (string) $request->input('body'),
            parentId: $request->input('parent_id') !== null ? (int) $request->input('parent_id') : null,
            status: $autoApprove ? DiscussionStatus::APPROVED : DiscussionStatus::PENDING,
            ipAddress: $request->ip(),
        );

        $discussion = $this->service->store($dto);

        return ApiResponse::fractalCreated(
            $discussion,
            $this->transformer,
            __('discussion::messages.created'),
        );
    }

    public function show(Discussion $discussion): JsonResponse
    {
        $this->authorize('view', $discussion);

        return ApiResponse::fractal(
            $discussion->load(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.show'),
        );
    }

    public function update(UpdateDiscussionRequest $request, Discussion $discussion): JsonResponse
    {
        $this->authorize('update', $discussion);

        $dto = new UpdateDiscussionDTO(
            body: $request->input('body'),
            status: $request->filled('status')
                ? DiscussionStatus::from($request->input('status'))
                : null,
        );

        $this->service->update($discussion, $dto);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.updated'),
        );
    }

    public function destroy(Discussion $discussion): JsonResponse
    {
        $this->authorize('delete', $discussion);

        $this->service->delete($discussion);

        return ApiResponse::success(null, __('discussion::messages.deleted'));
    }
}
