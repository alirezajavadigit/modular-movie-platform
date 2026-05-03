<?php

namespace Modules\Favorite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Favorite\Contracts\FavoriteRepositoryInterface;
use Modules\Favorite\Contracts\FavoriteServiceInterface;
use Modules\Favorite\DTOs\CreateFavoriteDTO;
use Modules\Favorite\Models\Favorite;

class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $repository,
    ) {}

    public function getUserFavorites(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByUser($userId, $perPage);
    }

    public function store(CreateFavoriteDTO $dto): Favorite
    {
        return $this->repository->create([
            'user_id'           => $dto->userId,
            'favoriteable_id'   => $dto->favoriteableId,
            'favoriteable_type' => $dto->favoriteableType,
        ]);
    }

    public function delete(Favorite $favorite): bool
    {
        return $this->repository->delete($favorite);
    }

    public function toggle(CreateFavoriteDTO $dto): array
    {
        $existing = $this->repository->findByUserAndFavoriteable(
            $dto->userId,
            $dto->favoriteableType,
            $dto->favoriteableId,
        );

        if ($existing) {
            $this->repository->delete($existing);

            return [
                'favorited' => false,
                'count'     => $this->repository->countByFavoriteable($dto->favoriteableType, $dto->favoriteableId),
            ];
        }

        $this->store($dto);

        return [
            'favorited' => true,
            'count'     => $this->repository->countByFavoriteable($dto->favoriteableType, $dto->favoriteableId),
        ];
    }

    public function isFavorited(int $userId, string $type, int $id): bool
    {
        return $this->repository->existsByUserAndFavoriteable($userId, $type, $id);
    }

    public function findExisting(int $userId, string $type, int $id): ?Favorite
    {
        return $this->repository->findByUserAndFavoriteable($userId, $type, $id);
    }
}
