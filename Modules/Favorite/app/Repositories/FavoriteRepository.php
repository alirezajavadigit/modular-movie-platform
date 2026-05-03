<?php

namespace Modules\Favorite\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Favorite\Contracts\FavoriteRepositoryInterface;
use Modules\Favorite\Models\Favorite;

final class FavoriteRepository implements FavoriteRepositoryInterface
{
    public function __construct(
        private readonly Favorite $model,
    ) {}

    public function findByUserAndFavoriteable(int $userId, string $type, int $id): ?Favorite
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->forFavoriteable($type, $id)
            ->first();
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->with('favoriteable')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Favorite
    {
        return $this->model->newQuery()->create($data);
    }

    public function delete(Favorite $favorite): bool
    {
        return (bool) $favorite->delete();
    }

    public function countByFavoriteable(string $type, int $id): int
    {
        return $this->model->newQuery()
            ->forFavoriteable($type, $id)
            ->count();
    }

    public function existsByUserAndFavoriteable(int $userId, string $type, int $id): bool
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->forFavoriteable($type, $id)
            ->exists();
    }
}
