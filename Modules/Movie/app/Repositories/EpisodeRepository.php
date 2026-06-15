<?php

namespace Modules\Movie\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Movie\Contracts\EpisodeRepositoryInterface;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Models\Episode;

class EpisodeRepository implements EpisodeRepositoryInterface
{
    public function __construct(
        private readonly Episode $model,
    ) {}

    public function getAllByMovie(int $movieId): Collection
    {
        return $this->model->newQuery()
            ->where('movie_id', $movieId)
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->get();
    }

    public function findById(int $id): ?Episode
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(CreateEpisodeDTO $dto): Episode
    {
        return $this->model->newQuery()->create([
            'movie_id'       => $dto->movieId,
            'season_number'  => $dto->seasonNumber,
            'episode_number' => $dto->episodeNumber,
            'title'          => $dto->title,
            'description'    => $dto->description,
            'poster'         => $dto->poster,
            'trailer_url'    => $dto->trailerUrl,
            'download_links' => $dto->downloadLinks,
        ]);
    }

    public function update(int $id, UpdateEpisodeDTO $dto): Episode
    {
        $episode = $this->model->newQuery()->findOrFail($id);

        $episode->update([
            'season_number'  => $dto->seasonNumber,
            'episode_number' => $dto->episodeNumber,
            'title'          => $dto->title,
            'description'    => $dto->description,
            'poster'         => $dto->poster,
            'trailer_url'    => $dto->trailerUrl,
            'download_links' => $dto->downloadLinks,
        ]);

        return $episode->fresh();
    }

    public function delete(int $id): bool
    {
        $episode = $this->model->newQuery()->findOrFail($id);

        return $episode->delete();
    }

    public function restore(int $id): Episode
    {
        $episode = $this->model->withTrashed()->findOrFail($id);
        $episode->restore();

        return $episode->fresh();
    }

    public function forceDelete(int $id): bool
    {
        $episode = $this->model->withTrashed()->findOrFail($id);

        return $episode->forceDelete();
    }
}
