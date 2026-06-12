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
use OpenApi\Attributes as OA;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    #[OA\Post(
        path: '/api/v1/discussions',
        operationId: 'discussion.store',
        summary: 'Post a discussion or reply',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreDiscussionRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/DiscussionCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/discussions/{discussion}',
        operationId: 'discussion.show',
        summary: 'Show a discussion with its approved replies',
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
    public function show(Discussion $discussion): JsonResponse
    {
        $this->authorize('view', $discussion);

        return ApiResponse::fractal(
            $discussion->load(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/discussions/{discussion}',
        operationId: 'discussion.update',
        summary: 'Update a discussion',
        security: [['bearerAuth' => []]],
        tags: ['Discussion'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateDiscussionRequest'),
        parameters: [
            new OA\Parameter(name: 'discussion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/DiscussionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Delete(
        path: '/api/v1/discussions/{discussion}',
        operationId: 'discussion.destroy',
        summary: 'Soft delete a discussion',
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
    public function destroy(Discussion $discussion): JsonResponse
    {
        $this->authorize('delete', $discussion);

        $this->service->delete($discussion);

        return ApiResponse::success(null, __('discussion::messages.deleted'));
    }
}
