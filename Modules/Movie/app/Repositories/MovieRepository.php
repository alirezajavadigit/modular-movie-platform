<?php

namespace Modules\Movie\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Movie\Contracts\MovieRepositoryInterface;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Models\Movie;

class MovieRepository implements MovieRepositoryInterface
{
    public function __construct(
        private readonly Movie $model,
    ) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?Movie
    {
        return $this->model->find($id);
    }

    public function create(CreateMovieDTO $dto): Movie
    {
        return $this->model->create([
            'title'          => $dto->title,
            'description'    => $dto->description,
            'poster'         => $dto->poster,
            'trailer_url'    => $dto->trailerUrl,
            'download_links' => $dto->downloadLinks,
            'release_year'   => $dto->releaseYear,
            'country'        => $dto->country,
            'language'       => $dto->language,
            'imdb_score'     => $dto->imdbScore,
            'badge'          => $dto->badge,
            'type'           => $dto->type,
        ]);
    }

    public function update(int $id, UpdateMovieDTO $dto): Movie
    {
        $movie = $this->model->findOrFail($id);

        $movie->update([
            'title'          => $dto->title,
            'description'    => $dto->description,
            'poster'         => $dto->poster,
            'trailer_url'    => $dto->trailerUrl,
            'download_links' => $dto->downloadLinks,
            'release_year'   => $dto->releaseYear,
            'country'        => $dto->country,
            'language'       => $dto->language,
            'imdb_score'     => $dto->imdbScore,
            'badge'          => $dto->badge,
        ]);

        return $movie->fresh();
    }

    public function delete(int $id): bool
    {
        $movie = $this->model->findOrFail($id);

        return $movie->delete();
    }

    public function restore(int $id): Movie
    {
        $movie = $this->model->withTrashed()->findOrFail($id);
        $movie->restore();

        return $movie->fresh();
    }

    public function forceDelete(int $id): bool
    {
        $movie = $this->model->withTrashed()->findOrFail($id);

        return $movie->forceDelete();
    }
}
