<?php

namespace Modules\Movie\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Models\Movie;

interface MovieRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?Movie;

    public function findByIdWithTrashed(int $id): ?Movie;

    public function create(CreateMovieDTO $dto): Movie;

    public function update(int $id, UpdateMovieDTO $dto): Movie;

    public function delete(int $id): bool;

    public function restore(int $id): Movie;

    public function forceDelete(int $id): bool;

    public function adminFilter(array $filters, int $perPage): LengthAwarePaginator;

    public function publicPaginated(string $q, string $type, int $perPage): LengthAwarePaginator;

    public function getTrashed(int $perPage): LengthAwarePaginator;
}
