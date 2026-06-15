<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Http\Resources\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;
use OpenApi\Attributes as OA;

class DiscussionQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/discussions',
        operationId: 'discussion.admin.index',
        summary: 'Admin: list all discussions with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'discussionable_type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['movie', 'episode', 'article'])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $filters = $request->only(['status', 'user_id', 'discussionable_type', 'trashed']);
        $discussions = $this->service->adminFilter($filters, $this->perPage($request));

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/discussions/{discussionableType}/{discussionableId}',
        operationId: 'discussion.byDiscussionable',
        summary: 'List approved discussions for a discussable resource',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussionableType', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['movie', 'episode', 'article'])),
            new OA\Parameter(name: 'discussionableId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byDiscussionable(Request $request, string $discussionableType, int $discussionableId): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $perPage = $this->perPage($request);
        $discussions = $this->service->getApprovedByDiscussionable($discussionableType, $discussionableId, $perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/discussions/user/{userId}',
        operationId: 'discussion.admin.byUser',
        summary: 'List discussions posted by a user',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $perPage = $this->perPage($request);
        $discussions = $this->service->getByUser($userId, $perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.by_user'),
        );
    }

    #[OA\Get(
        path: '/api/v1/discussions/{discussion}/replies',
        operationId: 'discussion.replies',
        summary: 'List approved replies of a discussion',
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionCollection')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function replies(Discussion $discussion): JsonResponse
    {
        $replies = $this->service->getReplies($discussion->id);

        return ApiResponse::fractalCollection(
            $replies,
            $this->transformer,
            __('discussion::messages.replies'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/discussions/approved',
        operationId: 'discussion.admin.approved',
        summary: 'List approved discussions',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function approved(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $perPage = $this->perPage($request);
        $discussions = $this->service->getApproved($perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.approved'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/discussions/rejected',
        operationId: 'discussion.admin.rejected',
        summary: 'List rejected discussions',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function rejected(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Discussion::class);

        $perPage = $this->perPage($request);
        $discussions = $this->service->getRejected($perPage);

        return ApiResponse::paginated(
            $discussions,
            $this->transformer,
            __('discussion::messages.rejected'),
        );
    }

    #[OA\Get(
        path: '/api/v1/discussions/pending/list',
        operationId: 'discussion.pending',
        summary: 'List pending discussions awaiting moderation',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewPending', Discussion::class);

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
