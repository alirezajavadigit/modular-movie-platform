<?php

namespace Modules\Subscription\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Models\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(int $id): ?Subscription;
    public function getAll(): Collection;
    public function getAllForUser(int $userId): Collection;
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function create(CreateSubscriptionDTO $dto): Subscription;
    public function update(Subscription $subscription, UpdateSubscriptionDTO $dto): Subscription;
    public function delete(Subscription $subscription): bool;
    public function forceDelete(Subscription $subscription): bool;
    public function restore(Subscription $subscription): Subscription;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
    public function exists(int $id): bool;
}
