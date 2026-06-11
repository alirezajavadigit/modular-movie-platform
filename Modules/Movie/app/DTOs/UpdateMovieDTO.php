<?php

namespace Modules\Movie\DTOs;

use Modules\Movie\Enums\BadgeType;

readonly class UpdateMovieDTO
{
    public function __construct(
        public array $title,
        public ?array $description,
        public ?string $poster,
        public ?string $trailerUrl,
        public ?array $downloadLinks,
        public int $releaseYear,
        public ?string $country,
        public ?string $language,
        public ?float $imdbScore,
        public BadgeType $badge,
    ) {}
}
