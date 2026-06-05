<?php

namespace Modules\Favorite\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Favorite\Models\Favorite;

interface FavoriteRepositoryInterface
{
    public function findByUserAndFavoriteable(int $userId, string $type, int $id): ?Favorite;

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Favorite;

    public function delete(Favorite $favorite): bool;

    public function countByFavoriteable(string $type, int $id): int;

    public function existsByUserAndFavoriteable(int $userId, string $type, int $id): bool;
}
