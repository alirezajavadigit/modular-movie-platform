<?php

namespace Modules\Category\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Models\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function findByField(string $field, mixed $value): Collection;

    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getActive(int $perPage = 15): LengthAwarePaginator;

    public function getByParent(?int $parentId, int $perPage = 15): LengthAwarePaginator;

    public function getTree(): Collection;

    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator;

    public function create(CreateCategoryDTO $dto): Category;

    public function update(int $id, UpdateCategoryDTO $dto): Category;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): Category;

    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function exists(int $id): bool;

    public function activate(int $id): Category;

    public function deactivate(int $id): Category;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
