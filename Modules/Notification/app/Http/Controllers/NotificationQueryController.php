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

class NotificationQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/notifications/filter/notifiable',
        operationId: 'notification.admin.forNotifiable',
        summary: 'List notifications of a notifiable entity',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'notifiable_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['user'])),
            new OA\Parameter(name: 'notifiable_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forNotifiable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');
        $perPage        = (int) $request->input('per_page', 15);

        $items = $this->service->getForNotifiable($notifiableType, $notifiableId, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications/filter/unread',
        operationId: 'notification.admin.unread',
        summary: 'List unread notifications of a notifiable entity',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'notifiable_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['user'])),
            new OA\Parameter(name: 'notifiable_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function unread(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');
        $perPage        = (int) $request->input('per_page', 15);

        $items = $this->service->getUnreadForNotifiable($notifiableType, $notifiableId, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.unread'));
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications/filter/by-type',
        operationId: 'notification.admin.byType',
        summary: 'List notifications of a registered type',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['user.welcome', 'user.password_reset', 'order.placed', 'order.status_changed', 'comment.received'])),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byType(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $type    = (string) $request->input('type');
        $perPage = (int) $request->input('per_page', 15);

        $items = $this->service->getByType($type, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications/meta/types',
        operationId: 'notification.admin.types',
        summary: 'List the registered notification types',
        security: [['bearerAuth' => []]],
        tags: ['Notification'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/NotificationTypes')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function types(): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $types = $this->service->registeredTypes();

        return ApiResponse::success($types, __('notification::messages.types'));
    }
}
