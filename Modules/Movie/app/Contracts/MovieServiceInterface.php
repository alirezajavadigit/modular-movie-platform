<?php

namespace Modules\Movie\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Models\Movie;

interface MovieServiceInterface
{
    public function getAllMovies(): Collection;

    public function getMovieById(int $id): Movie;

    public function createMovie(CreateMovieDTO $dto): Movie;

    public function updateMovie(int $id, UpdateMovieDTO $dto): Movie;

    public function deleteMovie(int $id): bool;

    public function restoreMovie(int $id): Movie;
}
