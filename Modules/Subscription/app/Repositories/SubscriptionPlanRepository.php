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

    public function getActivePaginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->active()
            ->latest()
            ->paginate($perPage);
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

    public function update(SubscriptionPlan $plan, UpdateSubscriptionPlanDTO $dto): SubscriptionPlan
    {
        $plan->update(array_filter([
            'name'          => $dto->name,
            'description'   => $dto->description,
            'price'         => $dto->price,
            'duration_days' => $dto->durationDays,
            'status'        => $dto->status,
        ], fn($value) => $value !== null));

        return $plan->refresh();
    }

    public function delete(SubscriptionPlan $plan): bool
    {
        return (bool) $plan->delete();
    }

    public function forceDelete(SubscriptionPlan $plan): bool
    {
        return (bool) $plan->forceDelete();
    }

    public function restore(SubscriptionPlan $plan): SubscriptionPlan
    {
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

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        match ($filters['trashed'] ?? 'without') {
            'with'  => $query->withTrashed(),
            'only'  => $query->onlyTrashed(),
            default => null,
        };

        return $query->latest()->paginate($perPage);
    }
}
