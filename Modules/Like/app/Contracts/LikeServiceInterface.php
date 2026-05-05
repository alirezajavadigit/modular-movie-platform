<?php

namespace Modules\Like\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Like\DTOs\CreateLikeDTO;
use Modules\Like\Models\Like;

interface LikeServiceInterface
{
    public function getUserLikes(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function store(CreateLikeDTO $dto): Like;

    public function delete(Like $like): bool;

    public function toggle(CreateLikeDTO $dto): array;

    public function isLiked(int $userId, string $type, int $id): bool;

    public function findExisting(int $userId, string $type, int $id): ?Like;
}
