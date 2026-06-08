<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Http\Resources\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;

class DiscussionStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    public function approve(Discussion $discussion): JsonResponse
    {
        $this->authorize('approve', $discussion);

        $this->service->approve($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.approved'),
        );
    }

    public function reject(Discussion $discussion): JsonResponse
    {
        $this->authorize('reject', $discussion);

        $this->service->reject($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.rejected'),
        );
    }

    public function markAsPending(Discussion $discussion): JsonResponse
    {
        $this->authorize('markAsPending', $discussion);

        $this->service->markAsPending($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.marked_as_pending'),
        );
    }
}
