<?php

namespace Modules\Like\DTOs;

readonly class CreateLikeDTO
{
    public function __construct(
        public int    $userId,
        public int    $likeableId,
        public string $likeableType,
    ) {}
}
