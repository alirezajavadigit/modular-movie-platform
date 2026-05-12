<?php

namespace Modules\Notification\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\DTOs\UpdateNotificationDTO;
use Modules\Notification\Models\Notification;

final class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private readonly Notification $model,
    ) {}

    public function findById(int $id): ?Notification
    {
        return $this->model->newQuery()->find($id);
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function create(CreateNotificationDTO $dto): Notification
    {
        return $this->model->newQuery()->create([
            'notifiable_type' => $dto->notifiableType,
            'notifiable_id'   => $dto->notifiableId,
            'type'            => $dto->type,
            'channel'         => $dto->channel,
            'data'            => $dto->data,
        ]);
    }

    public function update(int $id, UpdateNotificationDTO $dto): Notification
    {
        $notification = $this->model->newQuery()->findOrFail($id);

        $notification->update(array_filter([
            'type'    => $dto->type,
            'channel' => $dto->channel,
            'data'    => $dto->data,
        ], fn($value) => $value !== null));

        return $notification->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Notification
    {
        $notification = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $notification->restore();

        return $notification->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    public function markAsRead(int $id): Notification
    {
        $notification = $this->model->newQuery()->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return $notification->refresh();
    }

    public function markAllAsRead(string $notifiableType, int $notifiableId): bool
    {
        return (bool) $this->model->newQuery()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->latest()
            ->paginate($perPage);
    }

    public function getUnreadForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->latest()
            ->paginate($perPage);
    }

    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('type', $type)
            ->latest()
            ->paginate($perPage);
    }
}
