<?php

namespace Modules\Tag\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Tag\Contracts\TagRepositoryInterface;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Models\Tag;

final class TagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly Tag $model,
    ) {}

    public function findById(int $id): ?Tag
    {
        return $this->model->newQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Tag
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
        return $this->model->newQuery()->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->active()
            ->latest()
            ->paginate($perPage);
    }

    public function getInactive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->inactive()
            ->latest()
            ->paginate($perPage);
    }

    public function getPopular(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->withCount('articles')
            ->orderByDesc('articles_count')
            ->limit($limit)
            ->get();
    }

    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateTagDTO $dto): Tag
    {
        return $this->model->newQuery()->create([
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'color'       => $dto->color,
            'is_active'   => $dto->isActive,
        ]);
    }

    public function update(int $id, UpdateTagDTO $dto): Tag
    {
        $tag = $this->model->newQuery()->findOrFail($id);

        $tag->update(array_filter([
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'color'       => $dto->color,
            'is_active'   => $dto->isActive,
        ], fn($v) => !is_null($v)));

        return $tag->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Tag
    {
        $tag = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $tag->restore();

        return $tag->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    public function activate(int $id): Tag
    {
        $tag = $this->model->newQuery()->findOrFail($id);
        $tag->update(['is_active' => true]);

        return $tag->refresh();
    }

    public function deactivate(int $id): Tag
    {
        $tag = $this->model->newQuery()->findOrFail($id);
        $tag->update(['is_active' => false]);

        return $tag->refresh();
    }
}
