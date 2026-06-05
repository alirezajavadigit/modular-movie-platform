<?php

namespace Modules\Article\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Models\Article;

interface ArticleRepositoryInterface
{
    public function findById(int $id): ?Article;

    public function findBySlug(string $slug): ?Article;

    public function findByField(string $field, mixed $value): Collection;

    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getPublished(int $perPage = 15): LengthAwarePaginator;

    public function getDrafts(int $perPage = 15): LengthAwarePaginator;

    public function getArchived(int $perPage = 15): LengthAwarePaginator;

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    public function getByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getRelated(int $articleId, int $limit = 5): Collection;

    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    public function create(CreateArticleDTO $dto): Article;

    public function update(int $id, UpdateArticleDTO $dto): Article;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): Article;

    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function exists(int $id): bool;

    public function publish(int $id): Article;

    public function archive(int $id): Article;

    public function markAsDraft(int $id): Article;
}
