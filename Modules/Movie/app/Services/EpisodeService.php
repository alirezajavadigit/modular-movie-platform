<?php

namespace Modules\Movie\Services;

use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Movie\Contracts\EpisodeRepositoryInterface;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\Contracts\MovieRepositoryInterface;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Episode;

class EpisodeService implements EpisodeServiceInterface
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodeRepository,
        private readonly MovieRepositoryInterface $movieRepository,
    ) {}

    public function getAllEpisodes(int $movieId): Collection
    {
        $this->ensureSerialExists($movieId);

        return $this->episodeRepository->getAllByMovie($movieId);
    }

    public function getEpisodeById(int $movieId, int $episodeId): Episode
    {
        $this->ensureSerialExists($movieId);

        $episode = $this->episodeRepository->findById($episodeId);

        if (!$episode || $episode->movie_id !== $movieId) {
            throw new ModelNotFoundException("Episode with ID {$episodeId} not found for this serial.");
        }

        return $episode;
    }

    public function createEpisode(CreateEpisodeDTO $dto): Episode
    {
        $this->ensureSerialExists($dto->movieId);

        return $this->episodeRepository->create($dto);
    }

    public function updateEpisode(int $movieId, int $episodeId, UpdateEpisodeDTO $dto): Episode
    {
        $this->getEpisodeById($movieId, $episodeId);

        return $this->episodeRepository->update($episodeId, $dto);
    }

    public function deleteEpisode(int $movieId, int $episodeId): bool
    {
        $this->getEpisodeById($movieId, $episodeId);

        return $this->episodeRepository->delete($episodeId);
    }

    public function restoreEpisode(int $movieId, int $episodeId): Episode
    {
        $this->ensureSerialExists($movieId);

        return $this->episodeRepository->restore($episodeId);
    }

    private function ensureSerialExists(int $movieId): void
    {
        $movie = $this->movieRepository->findById($movieId);

        if (!$movie) {
            throw new ModelNotFoundException("Serial with ID {$movieId} not found.");
        }

        if ($movie->type !== MovieType::Serial) {
            throw new DomainException("Movie with ID {$movieId} is not a serial.");
        }
    }
}
