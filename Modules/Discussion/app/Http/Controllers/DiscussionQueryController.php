<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Http\Resources\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;

class DiscussionQueryController extends Controller
{
    public static string $modelClass = Discussion::class;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    public function byDiscussionable(Request $request, string $discussionableType, int $discussionableId): JsonResponse
    {
        $perPage = $this->perPage($request);
        $discussions = $this->service->getApprovedByDiscussionable($discussionableType, $discussionableId, $perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.index'),
        );
    }

    public function byUser(Request $request, int $userId): JsonResponse
    {
        $perPage = $this->perPage($request);
        $discussions = $this->service->getByUser($userId, $perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.by_user'),
        );
    }

    public function replies(Discussion $discussion): JsonResponse
    {
        $replies = $this->service->getReplies($discussion->id);

        return ApiResponse::fractalCollection(
            $replies,
            $this->transformer,
            __('discussion::messages.replies'),
        );
    }

    public function approved(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $discussions = $this->service->getApproved($perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.approved'),
        );
    }

    public function rejected(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $discussions = $this->service->getRejected($perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.rejected'),
        );
    }

    public function pending(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $discussions = $this->service->getPending($perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.pending'),
        );
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('discussion-module.per_page', 15);

        return min((int) $request->input('per_page', $default), 100);
    }
}
