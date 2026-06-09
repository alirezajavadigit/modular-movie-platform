<?php

namespace Modules\Category\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Category\Contracts\CategoryRepositoryInterface;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Models\Category;
use RuntimeException;

final class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Category
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        if (trim($slug) === '') {
            throw new InvalidArgumentException('Slug cannot be empty.');
        }
        return $this->repository->findBySlug($slug);
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->paginate($perPage);
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getActive($perPage);
    }
    public function getInactive(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getInactive($perPage);
    }

    public function getByParent(?int $parentId, int $perPage = 15): LengthAwarePaginator
    {
        if (!is_null($parentId) && $parentId <= 0) {
            throw new InvalidArgumentException('Parent ID must be a positive integer or null.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->getByParent($parentId, $perPage);
    }

    public function getTree(): Collection
    {
        return $this->repository->getTree();
    }

    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (trim($query) === '') {
            throw new InvalidArgumentException('Search query cannot be empty.');
        }
        if (mb_strlen($query) < 2) {
            throw new InvalidArgumentException('Search query must be at least 2 characters.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->searchAll($query, $perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (trim($query) === '') {
            throw new InvalidArgumentException('Search query cannot be empty.');
        }
        if (mb_strlen($query) < 2) {
            throw new InvalidArgumentException('Search query must be at least 2 characters.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->search($query, $perPage);
    }

    public function store(CreateCategoryDTO $dto): Category
    {
        if (empty($dto->name)) {
            throw new InvalidArgumentException('Category name is required.');
        }
        if (empty($dto->slug)) {
            throw new InvalidArgumentException('Category slug is required.');
        }

        $firstSlug = $dto->slug[array_key_first($dto->slug)];
        $existing = $this->repository->findBySlug($firstSlug);
        if ($existing) {
            throw new LogicException('A category with this slug already exists.');
        }

        if (!is_null($dto->parentId)) {
            $parent = $this->repository->findById($dto->parentId);
            if (!$parent) {
                throw new InvalidArgumentException("Parent category with ID {$dto->parentId} not found.");
            }
        }

        return DB::transaction(function () use ($dto): Category {
            $category = $this->repository->create($dto);
            if (!$category) {
                throw new RuntimeException('Failed to create category.');
            }
            return $category->refresh();
        });
    }

    public function update(int $id, UpdateCategoryDTO $dto): Category
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }

        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException("Category with ID {$id} not found.");
        }

        if (!is_null($dto->parentId)) {
            if ($dto->parentId === $id) {
                throw new LogicException('A category cannot be its own parent.');
            }
            $parent = $this->repository->findById($dto->parentId);
            if (!$parent) {
                throw new InvalidArgumentException("Parent category with ID {$dto->parentId} not found.");
            }
        }

        if (!is_null($dto->slug)) {
            $firstSlug = $dto->slug[array_key_first($dto->slug)];
            $existing = $this->repository->findBySlug($firstSlug);
            if ($existing && $existing->id !== $id) {
                throw new LogicException('Another category with this slug already exists.');
            }
        }

        return DB::transaction(function () use ($id, $dto): Category {
            $category = $this->repository->update($id, $dto);
            if (!$category) {
                throw new RuntimeException("Failed to update category with ID {$id}.");
            }
            return $category->refresh();
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException("Category with ID {$id} not found.");
        }
        if ($category->children()->exists()) {
            throw new LogicException('Cannot delete a category that has child categories.');
        }

        $result = $this->repository->delete($id);
        if (!$result) {
            throw new RuntimeException("Failed to delete category with ID {$id}.");
        }
        return $result;
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        return DB::transaction(function () use ($id): bool {
            $result = $this->repository->forceDelete($id);
            if (!$result) {
                throw new RuntimeException("Failed to permanently delete category with ID {$id}.");
            }
            return $result;
        });
    }

    public function restore(int $id): Category
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        $category = $this->repository->restore($id);
        if (!$category) {
            throw new RuntimeException("Failed to restore category with ID {$id}.");
        }
        return $category;
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getTrashed($perPage);
    }

    public function activate(int $id): Category
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException("Category with ID {$id} not found.");
        }
        if ($category->is_active) {
            throw new LogicException('Category is already active.');
        }
        return $this->repository->activate($id);
    }

    public function deactivate(int $id): Category
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer.');
        }
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException("Category with ID {$id} not found.");
        }
        if (!$category->is_active) {
            throw new LogicException('Category is already inactive.');
        }
        return $this->repository->deactivate($id);
    }

    private function guardPerPage(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }
    }
}
