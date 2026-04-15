<?php

namespace Modules\Movie\DTOs;

readonly class CreateEpisodeDTO
{
    public function __construct(
        public int $movieId,
        public int $seasonNumber,
        public int $episodeNumber,
        public string $title,
        public ?string $description,
        public ?string $poster,
        public ?string $trailerUrl,
        public ?array $downloadLinks,
    ) {}
}
