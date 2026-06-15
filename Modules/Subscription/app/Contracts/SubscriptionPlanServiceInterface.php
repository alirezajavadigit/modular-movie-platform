<?php

namespace Modules\Subscription\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionPlanDTO;
use Modules\Subscription\Models\SubscriptionPlan;

interface SubscriptionPlanServiceInterface
{
    public function findById(int $id): ?SubscriptionPlan;
    public function getAll(): Collection;
    public function getActive(): Collection;
    public function getActivePaginate(int $perPage = 15): LengthAwarePaginator;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function store(CreateSubscriptionPlanDTO $dto): SubscriptionPlan;
    public function update(SubscriptionPlan $plan, UpdateSubscriptionPlanDTO $dto): SubscriptionPlan;
    public function activate(SubscriptionPlan $plan): SubscriptionPlan;
    public function deactivate(SubscriptionPlan $plan): SubscriptionPlan;
    public function delete(SubscriptionPlan $plan): bool;
    public function forceDelete(SubscriptionPlan $plan): bool;
    public function restore(SubscriptionPlan $plan): SubscriptionPlan;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
