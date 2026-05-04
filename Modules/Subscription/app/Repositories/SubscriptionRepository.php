<?php

namespace Modules\Subscription\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private readonly Subscription $model,
    ) {}

    public function findById(int $id): ?Subscription
    {
        return $this->model->newQuery()->find($id);
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function getAllForUser(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function create(CreateSubscriptionDTO $dto): Subscription
    {
        return $this->model->newQuery()->create([
            'user_id' => $dto->userId,
            'plan_id' => $dto->planId,
            'status'  => SubscriptionStatus::default(),
        ]);
    }

    public function update(int $id, UpdateSubscriptionDTO $dto): Subscription
    {
        $subscription = $this->model->newQuery()->findOrFail($id);

        $subscription->update(array_filter([
            'status'     => $dto->status,
            'starts_at'  => $dto->startsAt,
            'ends_at'    => $dto->endsAt,
            'payment_id' => $dto->paymentId,
        ], fn($value) => $value !== null));

        return $subscription->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Subscription
    {
        $subscription = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $subscription->restore();

        return $subscription->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }
}
