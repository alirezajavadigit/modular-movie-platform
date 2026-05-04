<?php

namespace Modules\Subscription\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionPlanDTO;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Models\SubscriptionPlan;

final class SubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function __construct(
        private readonly SubscriptionPlan $model,
    ) {}

    public function findById(int $id): ?SubscriptionPlan
    {
        return $this->model->newQuery()->find($id);
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', SubscriptionPlanStatus::ACTIVE)
            ->latest()
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function create(CreateSubscriptionPlanDTO $dto): SubscriptionPlan
    {
        return $this->model->newQuery()->create([
            'name'          => $dto->name,
            'description'   => $dto->description,
            'price'         => $dto->price,
            'duration_days' => $dto->durationDays,
            'status'        => SubscriptionPlanStatus::default(),
        ]);
    }

    public function update(int $id, UpdateSubscriptionPlanDTO $dto): SubscriptionPlan
    {
        $plan = $this->model->newQuery()->findOrFail($id);

        $plan->update(array_filter([
            'name'          => $dto->name,
            'description'   => $dto->description,
            'price'         => $dto->price,
            'duration_days' => $dto->durationDays,
            'status'        => $dto->status,
        ], fn($value) => $value !== null));

        return $plan->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): SubscriptionPlan
    {
        $plan = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $plan->restore();

        return $plan->refresh();
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
