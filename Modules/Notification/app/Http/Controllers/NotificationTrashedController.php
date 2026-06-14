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

class NotificationTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/notifications/trashed',
        operationId: 'notification.admin.trashed',
        summary: 'List soft-deleted notifications',
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
        $this->authorize('viewTrashed', Notification::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/notifications/{notification}/restore',
        operationId: 'notification.admin.restore',
        summary: 'Restore a soft-deleted notification',
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
    public function restore(Notification $notification): JsonResponse
    {
        $this->authorize('restore', $notification);

        $restored = $this->service->restore($notification->id);

        return ApiResponse::fractal($restored, $this->transformer, __('notification::messages.restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/notifications/{notification}/force-delete',
        operationId: 'notification.admin.forceDelete',
        summary: 'Permanently delete a notification',
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
    public function forceDelete(Notification $notification): JsonResponse
    {
        $this->authorize('forceDelete', $notification);

        $this->service->forceDelete($notification->id);

        return ApiResponse::noContent(__('notification::messages.force_deleted'));
    }
}
