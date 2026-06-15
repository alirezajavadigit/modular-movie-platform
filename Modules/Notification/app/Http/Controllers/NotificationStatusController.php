<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\Http\Resources\Transformers\NotificationTransformer;
use Modules\Notification\Models\Notification;
use OpenApi\Attributes as OA;

class NotificationStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    #[OA\Patch(
        path: '/api/v1/admin/notifications/{notification}/read',
        operationId: 'notification.admin.markRead',
        summary: 'Mark a notification as read',
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
    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorize('markRead', $notification);

        $updated = $this->service->markAsRead($notification->id);

        return ApiResponse::fractal($updated, $this->transformer, __('notification::messages.read'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/notifications/read-all',
        operationId: 'notification.admin.markAllRead',
        summary: 'Mark all notifications of a notifiable entity as read',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'notifiable_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['user'])),
            new OA\Parameter(name: 'notifiable_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');

        $this->service->markAllAsRead($notifiableType, $notifiableId);

        return ApiResponse::noContent(__('notification::messages.all_read'));
    }
}
