<?php

namespace Modules\Movie\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Movie\Contracts\MovieRepositoryInterface;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Models\Movie;

class MovieService implements MovieServiceInterface
{
    public function __construct(
        private readonly MovieRepositoryInterface $movieRepository,
    ) {}

    public function getAllMovies(): Collection
    {
        return $this->movieRepository->getAll();
    }

    public function getMovieById(int $id): Movie
    {
        $movie = $this->movieRepository->findById($id);

        if (!$movie) {
            throw new ModelNotFoundException("Movie with ID {$id} not found.");
        }

        return $movie;
    }

    public function createMovie(CreateMovieDTO $dto): Movie
    {
        return $this->movieRepository->create($dto);
    }

    public function updateMovie(int $id, UpdateMovieDTO $dto): Movie
    {
        $this->getMovieById($id);

        return $this->movieRepository->update($id, $dto);
    }

    public function deleteMovie(int $id): bool
    {
        $this->getMovieById($id);

        return $this->movieRepository->delete($id);
    }

    public function restoreMovie(int $id): Movie
    {
        return $this->movieRepository->restore($id);
    }

    public function getMovieByIdWithTrashed(int $id): Movie
    {
        $movie = $this->movieRepository->findByIdWithTrashed($id);

        if (!$movie) {
            throw new ModelNotFoundException(__('movie::messages.movies.not_found'));
        }

        return $movie;
    }

    public function forceDeleteMovie(int $id): bool
    {
        return $this->movieRepository->forceDelete($id);
    }

    public function adminFilter(array $filters, int $perPage): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);

        return $this->movieRepository->adminFilter($filters, $perPage);
    }

    public function publicPaginated(string $q, string $type, int $perPage): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);

        return $this->movieRepository->publicPaginated($q, $type, $perPage);
    }

    public function getTrashed(int $perPage): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);

        return $this->movieRepository->getTrashed($perPage);
    }

    private function guardPerPage(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('per_page must be between 1 and 100.');
        }
    }
}
