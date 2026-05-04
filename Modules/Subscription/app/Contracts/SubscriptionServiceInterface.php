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
    public function activate(int $id): Subscription;
    public function cancel(int $id): Subscription;
    public function delete(int $id): bool;
    public function forceDelete(int $id): bool;
    public function restore(int $id): Subscription;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
}
