<?php

namespace Modules\Person\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Models\Person;

interface PersonRepositoryInterface
{
    public function findById(int $id): ?Person;

    public function findBySlug(string $slug): ?Person;

    public function findByField(string $field, mixed $value): Collection;

    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getActive(int $perPage = 15): LengthAwarePaginator;

    public function getInactive(int $perPage = 15): LengthAwarePaginator;

    public function getPopular(int $limit = 20): Collection;

    public function getByDepartment(string $department, int $perPage = 15): LengthAwarePaginator;

    public function getByGender(string $gender, int $perPage = 15): LengthAwarePaginator;

    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator;

    public function create(CreatePersonDTO $dto): Person;

    public function update(int $id, UpdatePersonDTO $dto): Person;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): Person;

    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function exists(int $id): bool;

    public function activate(int $id): Person;

    public function deactivate(int $id): Person;
}
