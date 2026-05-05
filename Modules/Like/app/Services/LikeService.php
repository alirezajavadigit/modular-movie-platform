<?php

namespace Modules\Like\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Like\Contracts\LikeRepositoryInterface;
use Modules\Like\Contracts\LikeServiceInterface;
use Modules\Like\DTOs\CreateLikeDTO;
use Modules\Like\Models\Like;

class LikeService implements LikeServiceInterface
{
    public function __construct(
        private readonly LikeRepositoryInterface $repository,
    ) {}

    public function getUserLikes(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByUser($userId, $perPage);
    }

    public function store(CreateLikeDTO $dto): Like
    {
        return $this->repository->create([
            'user_id'      => $dto->userId,
            'likeable_id'  => $dto->likeableId,
            'likeable_type' => $dto->likeableType,
        ]);
    }

    public function delete(Like $like): bool
    {
        return $this->repository->delete($like);
    }

    public function toggle(CreateLikeDTO $dto): array
    {
        $existing = $this->repository->findByUserAndLikeable(
            $dto->userId,
            $dto->likeableType,
            $dto->likeableId,
        );

        if ($existing) {
            $this->repository->delete($existing);

            return [
                'liked' => false,
                'count' => $this->repository->countByLikeable($dto->likeableType, $dto->likeableId),
            ];
        }

        $this->store($dto);

        return [
            'liked' => true,
            'count' => $this->repository->countByLikeable($dto->likeableType, $dto->likeableId),
        ];
    }

    public function isLiked(int $userId, string $type, int $id): bool
    {
        return $this->repository->existsByUserAndLikeable($userId, $type, $id);
    }

    public function findExisting(int $userId, string $type, int $id): ?Like
    {
        return $this->repository->findByUserAndLikeable($userId, $type, $id);
    }
}
