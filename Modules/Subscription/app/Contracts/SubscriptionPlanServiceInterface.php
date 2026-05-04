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
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function store(CreateSubscriptionPlanDTO $dto): SubscriptionPlan;
    public function update(int $id, UpdateSubscriptionPlanDTO $dto): SubscriptionPlan;
    public function activate(int $id): SubscriptionPlan;
    public function deactivate(int $id): SubscriptionPlan;
    public function delete(int $id): bool;
    public function forceDelete(int $id): bool;
    public function restore(int $id): SubscriptionPlan;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
}
