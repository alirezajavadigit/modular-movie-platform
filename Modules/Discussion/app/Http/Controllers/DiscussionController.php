<?php

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Http\Requests\StoreDiscussionRequest;
use Modules\Discussion\Http\Requests\UpdateDiscussionRequest;
use Modules\Discussion\Http\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $discussionService
    ) {}

    public function index(Request $request, string $discussionableType, int $discussionableId): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $map = config('discussion-module.discussionable_types', []);
        $morphClass = $map[$discussionableType] ?? $discussionableType;

        $discussions = $this->discussionService->getApprovedByDiscussionable(
            $morphClass,
            $discussionableId,
            (int) $request->query('per_page', config('discussion-module.pagination.per_page', 15))
        );

        return ApiResponse::fractal(
            $discussions,
            new DiscussionTransformer(),
            __('discussion-module::messages.discussions_retrieved')
        );
    }

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

        $discussion = $this->discussionService->store($dto);

        return ApiResponse::fractal(
            $discussion,
            new DiscussionTransformer(),
            __('discussion-module::messages.created_successfully'),
            201
        );
    }

    public function show(Discussion $discussion): JsonResponse
    {
        $this->authorize('view', $discussion);

        return ApiResponse::fractal(
            $discussion->load(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.discussion_retrieved')
        );
    }

    public function update(UpdateDiscussionRequest $request, Discussion $discussion): JsonResponse
    {
        $this->authorize('update', $discussion);

        $dto = new UpdateDiscussionDTO(
            body: $request->input('body'),
            status: $request->filled('status')
                ? DiscussionStatus::from($request->input('status'))
                : null
        );

        $this->discussionService->update($discussion, $dto);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.updated_successfully')
        );
    }

    public function destroy(Discussion $discussion): JsonResponse
    {
        $this->authorize('delete', $discussion);

        $this->discussionService->delete($discussion);

        return ApiResponse::success(
            null,
            __('discussion-module::messages.deleted_successfully')
        );
    }

    public function forceDelete(Discussion $discussion): JsonResponse
    {
        $this->authorize('forceDelete', $discussion);

        $this->discussionService->forceDelete($discussion);

        return ApiResponse::success(
            null,
            __('discussion-module::messages.force_deleted_successfully')
        );
    }

    public function restore(Discussion $discussion): JsonResponse
    {
        $this->authorize('restore', Discussion::class);

        $this->discussionService->restore($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.restored_successfully')
        );
    }

    public function approve(Discussion $discussion): JsonResponse
    {
        $this->authorize('approve', $discussion);

        $this->discussionService->approve($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.approved_successfully')
        );
    }

    public function reject(Discussion $discussion): JsonResponse
    {
        $this->authorize('reject', $discussion);

        $this->discussionService->reject($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.rejected_successfully')
        );
    }

    public function markAsPending(Discussion $discussion): JsonResponse
    {
        $this->authorize('markAsPending', $discussion);

        $this->discussionService->markAsPending($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            new DiscussionTransformer(),
            __('discussion-module::messages.marked_as_pending_successfully')
        );
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewPending', Discussion::class);

        $discussions = $this->discussionService->getPending(
            (int) $request->query('per_page', config('discussion-module.pagination.per_page', 15))
        );

        return ApiResponse::fractal(
            $discussions,
            new DiscussionTransformer(),
            __('discussion-module::messages.pending_discussions_retrieved')
        );
    }

    public function userDiscussions(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $discussions = $this->discussionService->getByUser(
            $userId,
            (int) $request->query('per_page', config('discussion-module.pagination.per_page', 15))
        );

        return ApiResponse::fractal(
            $discussions,
            new DiscussionTransformer(),
            __('discussion-module::messages.user_discussions_retrieved')
        );
    }

    public function replies(Discussion $discussion): JsonResponse
    {
        $this->authorize('view', $discussion);

        $replies = $this->discussionService->getReplies($discussion->id);

        return ApiResponse::fractal(
            $replies,
            new DiscussionTransformer(),
            __('discussion-module::messages.replies_retrieved')
        );
    }
}
