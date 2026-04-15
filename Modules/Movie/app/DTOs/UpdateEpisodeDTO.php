<?php

namespace Modules\Movie\DTOs;

readonly class UpdateEpisodeDTO
{
    public function __construct(
        public int $seasonNumber,
        public int $episodeNumber,
        public string $title,
        public ?string $description,
        public ?string $poster,
        public ?string $trailerUrl,
        public ?array $downloadLinks,
    ) {}
}
