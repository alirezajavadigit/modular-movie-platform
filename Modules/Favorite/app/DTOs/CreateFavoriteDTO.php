<?php

namespace Modules\Favorite\DTOs;

readonly class CreateFavoriteDTO
{
    public function __construct(
        public int    $userId,
        public int    $favoriteableId,
        public string $favoriteableType,
    ) {}

    public static function fromRequest(int $userId, int $favoriteableId, string $favoriteableType): self
    {
        return new self(
            userId: $userId,
            favoriteableId: $favoriteableId,
            favoriteableType: $favoriteableType,
        );
    }
}
