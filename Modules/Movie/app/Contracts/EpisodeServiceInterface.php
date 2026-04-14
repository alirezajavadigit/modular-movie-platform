<?php

namespace Modules\Movie\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Models\Episode;

interface EpisodeServiceInterface
{
    public function getAllEpisodes(int $movieId): Collection;

    public function getEpisodeById(int $movieId, int $episodeId): Episode;

    public function createEpisode(CreateEpisodeDTO $dto): Episode;

    public function updateEpisode(int $movieId, int $episodeId, UpdateEpisodeDTO $dto): Episode;

    public function deleteEpisode(int $movieId, int $episodeId): bool;

    public function restoreEpisode(int $movieId, int $episodeId): Episode;
}
