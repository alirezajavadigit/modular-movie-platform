<?php

namespace Modules\Movie\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Models\Movie;

interface MovieServiceInterface
{
    public function getAllMovies(): Collection;

    public function getMovieById(int $id): Movie;

    public function getMovieByIdWithTrashed(int $id): Movie;

    public function createMovie(CreateMovieDTO $dto): Movie;

    public function updateMovie(int $id, UpdateMovieDTO $dto): Movie;

    public function deleteMovie(int $id): bool;

    public function restoreMovie(int $id): Movie;

    public function forceDeleteMovie(int $id): bool;

    public function adminFilter(array $filters, int $perPage): LengthAwarePaginator;

    public function publicPaginated(string $q, string $type, int $perPage): LengthAwarePaginator;

    public function getTrashed(int $perPage): LengthAwarePaginator;
}
