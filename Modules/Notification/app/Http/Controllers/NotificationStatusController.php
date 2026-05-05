<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\Http\Resources\Transformers\NotificationTransformer;
use Modules\Notification\Models\Notification;

class NotificationStatusController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    public function markRead(Notification $notification): JsonResponse
    {
        $updated = $this->service->markAsRead($notification->id);

        return ApiResponse::fractal($updated, $this->transformer, __('notification::messages.read'));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $notifiableType = (string) $request->input('notifiable_type');
        $notifiableId   = (int) $request->input('notifiable_id');

        $this->service->markAllAsRead($notifiableType, $notifiableId);

        return ApiResponse::noContent(__('notification::messages.all_read'));
    }
}
