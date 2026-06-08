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

class NotificationQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    public function forNotifiable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');
        $perPage        = (int) $request->input('per_page', 15);

        $items = $this->service->getForNotifiable($notifiableType, $notifiableId, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

    public function unread(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');
        $perPage        = (int) $request->input('per_page', 15);

        $items = $this->service->getUnreadForNotifiable($notifiableType, $notifiableId, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.unread'));
    }

    public function byType(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $type    = (string) $request->input('type');
        $perPage = (int) $request->input('per_page', 15);

        $items = $this->service->getByType($type, $perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

    public function types(): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $types = $this->service->registeredTypes();

        return ApiResponse::success($types, __('notification::messages.types'));
    }
}
