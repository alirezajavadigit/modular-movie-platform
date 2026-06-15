<?php

namespace Modules\Subscription\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Models\Subscription;

interface SubscriptionServiceInterface
{
    public function findById(int $id): ?Subscription;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function getAllForUser(int $userId): Collection;
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    public function subscribe(CreateSubscriptionDTO $dto): string;
    public function activate(Subscription $subscription): Subscription;
    public function cancel(Subscription $subscription): Subscription;
    public function delete(Subscription $subscription): bool;
    public function forceDelete(Subscription $subscription): bool;
    public function restore(Subscription $subscription): Subscription;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
