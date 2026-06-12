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

class DiscussionTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    #[OA\Post(
        path: '/api/v1/discussions/{discussion}/restore',
        operationId: 'discussion.restore',
        summary: 'Restore a soft-deleted discussion',
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
    public function restore(Discussion $discussion): JsonResponse
    {
        $this->authorize('restore', Discussion::class);

        $this->service->restore($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.restored'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/discussions/{discussion}/force',
        operationId: 'discussion.forceDelete',
        summary: 'Permanently delete a discussion',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SuccessMessage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(Discussion $discussion): JsonResponse
    {
        $this->authorize('forceDelete', $discussion);

        $this->service->forceDelete($discussion);

        return ApiResponse::success(null, __('discussion::messages.force_deleted'));
    }
}
