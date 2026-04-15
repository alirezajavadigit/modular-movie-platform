<?php

namespace Modules\Movie\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
}
