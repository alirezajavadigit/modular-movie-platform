<?php

namespace Modules\Like\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Like\Models\Like;

interface LikeRepositoryInterface
{
    public function findByUserAndLikeable(int $userId, string $type, int $id): ?Like;

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Like;

    public function delete(Like $like): bool;

    public function countByLikeable(string $type, int $id): int;

    public function existsByUserAndLikeable(int $userId, string $type, int $id): bool;
}
