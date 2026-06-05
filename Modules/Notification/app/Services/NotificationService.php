<?php

namespace Modules\Notification\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\DTOs\UpdateNotificationDTO;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\Notification;

final class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Notification
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        return $this->repository->findById($id);
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->paginate($perPage);
    }

    public function store(CreateNotificationDTO $dto): Notification
    {
        $this->assertTypeIsRegistered($dto->type);
        $this->assertChannelAllowedForType($dto->type, $dto->channel);
        $this->assertNotifiableTypeIsRegistered($dto->notifiableType);

        return DB::transaction(function () use ($dto): Notification {
            $notification = $this->repository->create($dto);

            if (!$notification) {
                throw new RuntimeException('Failed to create notification.');
            }

            return $notification->refresh();
        });
    }

    public function update(int $id, UpdateNotificationDTO $dto): Notification
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        $notification = $this->repository->findById($id);

        if (!$notification) {
            throw new InvalidArgumentException("Notification with ID {$id} not found.");
        }

        if ($dto->type !== null) {
            $this->assertTypeIsRegistered($dto->type);
        }

        if ($dto->channel !== null && $dto->type !== null) {
            $this->assertChannelAllowedForType($dto->type, $dto->channel);
        }

        if ($dto->channel !== null && $dto->type === null) {
            $this->assertChannelAllowedForType($notification->type, $dto->channel);
        }

        return DB::transaction(function () use ($id, $dto): Notification {
            return $this->repository->update($id, $dto);
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        $notification = $this->repository->findById($id);

        if (!$notification) {
            throw new InvalidArgumentException("Notification with ID {$id} not found.");
        }

        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        return DB::transaction(function () use ($id): bool {
            return $this->repository->forceDelete($id);
        });
    }

    public function restore(int $id): Notification
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        return DB::transaction(function () use ($id): Notification {
            return $this->repository->restore($id);
        });
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getTrashed($perPage);
    }

    public function markAsRead(int $id): Notification
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Notification ID must be a positive integer.');
        }

        $notification = $this->repository->findById($id);

        if (!$notification) {
            throw new InvalidArgumentException("Notification with ID {$id} not found.");
        }

        if ($notification->isRead()) {
            throw new LogicException('Notification is already marked as read.');
        }

        return DB::transaction(function () use ($id): Notification {
            return $this->repository->markAsRead($id);
        });
    }

    public function markAllAsRead(string $notifiableType, int $notifiableId): bool
    {
        $notifiableType = $this->resolveNotifiableType($notifiableType);
        $this->assertNotifiableTypeIsRegistered($notifiableType);

        if ($notifiableId <= 0) {
            throw new InvalidArgumentException('Notifiable ID must be a positive integer.');
        }

        return DB::transaction(function () use ($notifiableType, $notifiableId): bool {
            return $this->repository->markAllAsRead($notifiableType, $notifiableId);
        });
    }

    public function getForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator
    {
        $notifiableType = $this->resolveNotifiableType($notifiableType);
        $this->assertNotifiableTypeIsRegistered($notifiableType);

        if ($notifiableId <= 0) {
            throw new InvalidArgumentException('Notifiable ID must be a positive integer.');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getForNotifiable($notifiableType, $notifiableId, $perPage);
    }

    public function getUnreadForNotifiable(string $notifiableType, int $notifiableId, int $perPage = 15): LengthAwarePaginator
    {
        $notifiableType = $this->resolveNotifiableType($notifiableType);
        $this->assertNotifiableTypeIsRegistered($notifiableType);

        if ($notifiableId <= 0) {
            throw new InvalidArgumentException('Notifiable ID must be a positive integer.');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getUnreadForNotifiable($notifiableType, $notifiableId, $perPage);
    }

    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        $this->assertTypeIsRegistered($type);

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getByType($type, $perPage);
    }

    public function resolveNotifiableType(string $morphAlias): string
    {
        $morphMap = $this->morphMap();

        if (!isset($morphMap[$morphAlias])) {
            throw new InvalidArgumentException(
                "Morph alias [{$morphAlias}] is not registered in the morph map. "
                    . "Add it to config/config.php under 'morph_map'.",
            );
        }

        return $morphMap[$morphAlias];
    }

    public function registeredTypes(): array
    {
        return config('notification-module.notification_types', []);
    }

    private function assertTypeIsRegistered(string $type): void
    {
        $types = $this->registeredTypes();

        if (!array_key_exists($type, $types)) {
            throw new InvalidArgumentException(
                "Notification type [{$type}] is not registered. "
                    . "Add it to config/config.php under 'notification_types'.",
            );
        }
    }

    private function assertChannelAllowedForType(string $type, NotificationChannel $channel): void
    {
        $types           = $this->registeredTypes();
        $allowedChannels = $types[$type]['channels'] ?? [];

        if (!in_array($channel->value, $allowedChannels, true)) {
            throw new LogicException(
                "Channel [{$channel->value}] is not allowed for notification type [{$type}]. "
                    . "Allowed channels: " . implode(', ', $allowedChannels) . '.',
            );
        }
    }

    private function assertNotifiableTypeIsRegistered(string $notifiableType): void
    {
        if (!in_array($notifiableType, $this->morphMap(), true)) {
            throw new InvalidArgumentException(
                "Notifiable type [{$notifiableType}] is not a registered morph map class.",
            );
        }
    }

    private function morphMap(): array
    {
        return config('notification-module.morph_map', []);
    }
}
