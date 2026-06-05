<?php

namespace Modules\Category\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Category\Contracts\CategoryRepositoryInterface;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Models\Category;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly Category $model,
    ) {}

    public function findById(int $id): ?Category
    {
        return $this->model->newQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->newQuery()
            ->where('slug->' . app()->getLocale(), $slug)
            ->first();
    }

    public function findByField(string $field, mixed $value): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get();
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->orderBy('order')->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->orderBy('order')->latest()->paginate($perPage);
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('order')
            ->latest()
            ->paginate($perPage);
    }

    public function getByParent(?int $parentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->latest()
            ->paginate($perPage);
    }

    public function getTree(): Collection
    {
        return $this->model->newQuery()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('order')
            ->get();
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function create(CreateCategoryDTO $dto): Category
    {
        return $this->model->newQuery()->create([
            'parent_id'   => $dto->parentId,
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
            'order'       => $dto->order,
        ]);
    }

    public function update(int $id, UpdateCategoryDTO $dto): Category
    {
        $category = $this->model->newQuery()->findOrFail($id);

        $category->update(array_filter([
            'parent_id'   => $dto->parentId,
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
            'order'       => $dto->order,
        ], fn($v) => !is_null($v)));

        return $category->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Category
    {
        $category = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $category->restore();

        return $category->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    public function activate(int $id): Category
    {
        $category = $this->model->newQuery()->findOrFail($id);
        $category->update(['is_active' => true]);

        return $category->refresh();
    }

    public function deactivate(int $id): Category
    {
        $category = $this->model->newQuery()->findOrFail($id);
        $category->update(['is_active' => false]);

        return $category->refresh();
    }
}
