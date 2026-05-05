<?php

namespace Modules\Like\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Like\Contracts\LikeRepositoryInterface;
use Modules\Like\Models\Like;

final class LikeRepository implements LikeRepositoryInterface
{
    public function __construct(
        private readonly Like $model,
    ) {}

    public function findByUserAndLikeable(int $userId, string $type, int $id): ?Like
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->forLikeable($type, $id)
            ->first();
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->with('likeable')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Like
    {
        return $this->model->newQuery()->create($data);
    }

    public function delete(Like $like): bool
    {
        return (bool) $like->delete();
    }

    public function countByLikeable(string $type, int $id): int
    {
        return $this->model->newQuery()
            ->forLikeable($type, $id)
            ->count();
    }

    public function existsByUserAndLikeable(int $userId, string $type, int $id): bool
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->forLikeable($type, $id)
            ->exists();
    }
}
