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

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationServiceInterface $service,
        private readonly NotificationTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->paginate($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('notification::messages.index'));
    }

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

    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        return ApiResponse::fractal($notification, $this->transformer, __('notification::messages.show'));
    }

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

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->service->delete($notification->id);

        return ApiResponse::noContent(__('notification::messages.deleted'));
    }
}
