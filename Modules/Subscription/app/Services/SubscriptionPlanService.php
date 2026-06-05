<?php

namespace Modules\Subscription\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionPlanDTO;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Models\SubscriptionPlan;

final class SubscriptionPlanService implements SubscriptionPlanServiceInterface
{
    public function __construct(
        private readonly SubscriptionPlanRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?SubscriptionPlan
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Subscription plan ID must be a positive integer.');
        }

        return $this->repository->findById($id);
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    public function getActivePaginate(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getActivePaginate($perPage);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->paginate($perPage);
    }

    public function store(CreateSubscriptionPlanDTO $dto): SubscriptionPlan
    {
        return DB::transaction(function () use ($dto): SubscriptionPlan {
            $plan = $this->repository->create($dto);

            if (!$plan) {
                throw new RuntimeException('Failed to create subscription plan.');
            }

            return $plan->refresh();
        });
    }

    public function update(SubscriptionPlan $plan, UpdateSubscriptionPlanDTO $dto): SubscriptionPlan
    {
        return DB::transaction(fn(): SubscriptionPlan => $this->repository->update($plan, $dto));
    }

    public function activate(SubscriptionPlan $plan): SubscriptionPlan
    {
        if ($plan->status->isActive()) {
            throw new LogicException('Subscription plan is already active.');
        }

        return DB::transaction(fn(): SubscriptionPlan => $this->repository->update($plan, new UpdateSubscriptionPlanDTO(
            status: SubscriptionPlanStatus::ACTIVE,
        )));
    }

    public function deactivate(SubscriptionPlan $plan): SubscriptionPlan
    {
        if ($plan->status->isInactive()) {
            throw new LogicException('Subscription plan is already inactive.');
        }

        return DB::transaction(fn(): SubscriptionPlan => $this->repository->update($plan, new UpdateSubscriptionPlanDTO(
            status: SubscriptionPlanStatus::INACTIVE,
        )));
    }

    public function delete(SubscriptionPlan $plan): bool
    {
        return DB::transaction(fn(): bool => $this->repository->delete($plan));
    }

    public function forceDelete(SubscriptionPlan $plan): bool
    {
        return DB::transaction(fn(): bool => $this->repository->forceDelete($plan));
    }

    public function restore(SubscriptionPlan $plan): SubscriptionPlan
    {
        return DB::transaction(fn(): SubscriptionPlan => $this->repository->restore($plan));
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getTrashed($perPage);
    }
}
