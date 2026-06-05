<?php

namespace Modules\Favorite\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Favorite\DTOs\CreateFavoriteDTO;
use Modules\Favorite\Models\Favorite;

interface FavoriteServiceInterface
{
    public function getUserFavorites(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function store(CreateFavoriteDTO $dto): Favorite;

    public function delete(Favorite $favorite): bool;

    public function toggle(CreateFavoriteDTO $dto): array;

    public function isFavorited(int $userId, string $type, int $id): bool;

    public function findExisting(int $userId, string $type, int $id): ?Favorite;
}
