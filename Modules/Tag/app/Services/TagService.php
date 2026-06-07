<?php

namespace Modules\Tag\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Tag\Contracts\TagRepositoryInterface;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Models\Tag;
use RuntimeException;

final class TagService implements TagServiceInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Tag
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?Tag
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

    public function getPopular(int $limit = 10): Collection
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Limit must be between 1 and 100.');
        }
        return $this->repository->getPopular($limit);
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

    public function store(CreateTagDTO $dto): Tag
    {
        if (empty($dto->name)) {
            throw new InvalidArgumentException('Tag name is required.');
        }
        if (empty($dto->slug)) {
            throw new InvalidArgumentException('Tag slug is required.');
        }

        $firstSlug = $dto->slug[array_key_first($dto->slug)];
        $existing = $this->repository->findBySlug($firstSlug);
        if ($existing) {
            throw new LogicException('A tag with this slug already exists.');
        }

        return DB::transaction(function () use ($dto): Tag {
            $tag = $this->repository->create($dto);
            if (!$tag) {
                throw new RuntimeException('Failed to create tag.');
            }
            return $tag->refresh();
        });
    }

    public function update(int $id, UpdateTagDTO $dto): Tag
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }

        $tag = $this->repository->findById($id);
        if (!$tag) {
            throw new InvalidArgumentException("Tag with ID {$id} not found.");
        }

        if (!is_null($dto->slug)) {
            $firstSlug = $dto->slug[array_key_first($dto->slug)];
            $existing = $this->repository->findBySlug($firstSlug);
            if ($existing && $existing->id !== $id) {
                throw new LogicException('Another tag with this slug already exists.');
            }
        }

        return DB::transaction(function () use ($id, $dto): Tag {
            $tag = $this->repository->update($id, $dto);
            if (!$tag) {
                throw new RuntimeException("Failed to update tag with ID {$id}.");
            }
            return $tag->refresh();
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        $tag = $this->repository->findById($id);
        if (!$tag) {
            throw new InvalidArgumentException("Tag with ID {$id} not found.");
        }

        $result = $this->repository->delete($id);
        if (!$result) {
            throw new RuntimeException("Failed to delete tag with ID {$id}.");
        }
        return $result;
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        return DB::transaction(function () use ($id): bool {
            $result = $this->repository->forceDelete($id);
            if (!$result) {
                throw new RuntimeException("Failed to permanently delete tag with ID {$id}.");
            }
            return $result;
        });
    }

    public function restore(int $id): Tag
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        $tag = $this->repository->restore($id);
        if (!$tag) {
            throw new RuntimeException("Failed to restore tag with ID {$id}.");
        }
        return $tag;
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getTrashed($perPage);
    }

    public function activate(int $id): Tag
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        $tag = $this->repository->findById($id);
        if (!$tag) {
            throw new InvalidArgumentException("Tag with ID {$id} not found.");
        }
        if ($tag->is_active) {
            throw new LogicException('Tag is already active.');
        }
        return $this->repository->activate($id);
    }

    public function deactivate(int $id): Tag
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tag ID must be a positive integer.');
        }
        $tag = $this->repository->findById($id);
        if (!$tag) {
            throw new InvalidArgumentException("Tag with ID {$id} not found.");
        }
        if (!$tag->is_active) {
            throw new LogicException('Tag is already inactive.');
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
