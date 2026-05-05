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

class NotificationTrashedController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.trashed'));
    }

    public function restore(Notification $trashedNotification): JsonResponse
    {
        $restored = $this->service->restore($trashedNotification->id);

        return ApiResponse::fractal($restored, $this->transformer, __('notification::messages.restored'));
    }

    public function forceDelete(Notification $trashedNotification): JsonResponse
    {
        $this->service->forceDelete($trashedNotification->id);

        return ApiResponse::noContent(__('notification::messages.force_deleted'));
    }
}
