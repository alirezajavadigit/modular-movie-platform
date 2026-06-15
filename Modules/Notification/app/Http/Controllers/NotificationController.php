<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\DTOs\UpdateNotificationDTO;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Http\Requests\StoreNotificationRequest;
use Modules\Notification\Http\Requests\UpdateNotificationRequest;
use Modules\Notification\Http\Resources\Transformers\NotificationTransformer;
use Modules\Notification\Models\Notification;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/notifications',
        operationId: 'notification.admin.index',
        summary: 'List all notifications',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->paginate($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications',
        operationId: 'notification.admin.store',
        summary: 'Create and dispatch a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreNotificationRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/NotificationCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $this->authorize('create', Notification::class);

        $validated = $request->validated();

        $dto = new CreateNotificationDTO(
            notifiableType: $this->service->resolveNotifiableType($validated['notifiable_type']),
            notifiableId:   (int) $validated['notifiable_id'],
            type:           $validated['type'],
            channel:        NotificationChannel::from($validated['channel']),
            data:           $validated['data'] ?? [],
        );

        $notification = $this->service->store($dto);

        return ApiResponse::fractalCreated($notification, $this->transformer, __('notification::messages.created'));
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications/{notification}',
        operationId: 'notification.admin.show',
        summary: 'Show a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        return ApiResponse::fractal($notification, $this->transformer, __('notification::messages.show'));
    }

    #[OA\Put(
        path: '/api/v1/admin/notifications/{notification}',
        operationId: 'notification.admin.update',
        summary: 'Update a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateNotificationRequest'),
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $validated = $request->validated();

        $dto = new UpdateNotificationDTO(
            type:    $validated['type'] ?? null,
            channel: isset($validated['channel']) ? NotificationChannel::from($validated['channel']) : null,
            data:    $validated['data'] ?? null,
        );

        $updated = $this->service->update($notification->id, $dto);

        return ApiResponse::fractal($updated, $this->transformer, __('notification::messages.updated'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/notifications/{notification}',
        operationId: 'notification.admin.destroy',
        summary: 'Soft delete a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->service->delete($notification->id);

        return ApiResponse::noContent(__('notification::messages.deleted'));
    }
}
