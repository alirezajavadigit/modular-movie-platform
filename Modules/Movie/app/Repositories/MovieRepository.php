<?php

namespace Modules\Movie\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
        return $this->model->newQuery()->get();
    }

    public function findById(int $id): ?Movie
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(CreateMovieDTO $dto): Movie
    {
        return $this->model->newQuery()->create([
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
        $movie = $this->model->newQuery()->findOrFail($id);

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
        $movie = $this->model->newQuery()->findOrFail($id);

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

    public function findByIdWithTrashed(int $id): ?Movie
    {
        return $this->model->withTrashed()->find($id);
    }

    public function adminFilter(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['q'])) {
            $query->where('title', 'LIKE', "%{$filters['q']}%");
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['badge'])) {
            $query->where('badge', $filters['badge']);
        }
        if (!empty($filters['year_from'])) {
            $query->where('release_year', '>=', (int) $filters['year_from']);
        }
        if (!empty($filters['year_to'])) {
            $query->where('release_year', '<=', (int) $filters['year_to']);
        }
        if (!empty($filters['country'])) {
            $query->where('country', 'LIKE', "%{$filters['country']}%");
        }
        if (!empty($filters['language'])) {
            $query->where('language', 'LIKE', "%{$filters['language']}%");
        }
        if (isset($filters['imdb_min']) && $filters['imdb_min'] !== '') {
            $query->where('imdb_score', '>=', (float) $filters['imdb_min']);
        }
        if (isset($filters['imdb_max']) && $filters['imdb_max'] !== '') {
            $query->where('imdb_score', '<=', (float) $filters['imdb_max']);
        }

        match ($filters['trashed'] ?? 'without') {
            'with'  => $query->withTrashed(),
            'only'  => $query->onlyTrashed(),
            default => null,
        };

        return $query->latest()->paginate($perPage);
    }

    public function publicPaginated(string $q, string $type, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($q !== '') {
            $query->where('title', 'LIKE', "%{$q}%");
        }
        if ($type !== '') {
            $query->where('type', $type);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getTrashed(int $perPage): LengthAwarePaginator
    {
        return $this->model->onlyTrashed()->latest()->paginate($perPage);
    }
}
