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
use OpenApi\Attributes as OA;

class DiscussionStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    #[OA\Post(
        path: '/api/v1/discussions/{discussion}/approve',
        operationId: 'discussion.approve',
        summary: 'Approve a discussion',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/discussions/{discussion}/reject',
        operationId: 'discussion.reject',
        summary: 'Reject a discussion',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/discussions/{discussion}/pending',
        operationId: 'discussion.markAsPending',
        summary: 'Move a discussion back to pending',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
