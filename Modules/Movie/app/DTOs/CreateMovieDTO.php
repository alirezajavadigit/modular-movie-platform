<?php

namespace Modules\Movie\DTOs;

use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;

readonly class CreateMovieDTO
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
        public MovieType $type,
    ) {}
}
