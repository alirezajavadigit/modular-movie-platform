<?php

namespace Modules\Notification\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\DTOs\UpdateNotificationDTO;
use Modules\Notification\Models\Notification;

interface NotificationRepositoryInterface
{
    public function findById(int $id): ?Notification;
    public function getAll(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function create(CreateNotificationDTO $dto): Notification;
    public function update(int $id, UpdateNotificationDTO $dto): Notification;
    public function delete(int $id): bool;
    public function forceDelete(int $id): bool;
    public function restore(int $id): Notification;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
    public function exists(int $id): bool;
    public function markAsRead(int $id): Notification;
    public function markAllAsRead(string $notifiableType, int $notifiableId): bool;
    public function getForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator;
    public function getUnreadForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator;
    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator;
}
