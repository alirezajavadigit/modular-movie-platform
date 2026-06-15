<?php

namespace Modules\Tag\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Models\Tag;

interface TagRepositoryInterface
{
    public function findById(int $id): ?Tag;

    public function findBySlug(string $slug): ?Tag;

    public function findByField(string $field, mixed $value): Collection;

    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getActive(int $perPage = 15): LengthAwarePaginator;

    public function getInactive(int $perPage = 15): LengthAwarePaginator;

    public function getPopular(int $limit = 10): Collection;

    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator;

    public function create(CreateTagDTO $dto): Tag;

    public function update(int $id, UpdateTagDTO $dto): Tag;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): Tag;

    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function exists(int $id): bool;

    public function activate(int $id): Tag;

    public function deactivate(int $id): Tag;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
