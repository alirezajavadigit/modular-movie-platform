<?php

namespace Modules\Article\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Models\Article;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository,
    ) {}
    public function findById(int $id): ?Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        return $this->repository->findById($id);
    }
    public function findBySlug(string $slug): ?Article
    {
        if (trim($slug) === '') {
            throw new InvalidArgumentException("Slug cannot be empty.");
        }
        return $this->repository->findBySlug($slug);
    }
    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->paginate($perPage);
    }
    public function getPublished(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getPublished($perPage);
    }
    public function getDrafts(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getDrafts($perPage);
    }
    public function getArchived(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getArchived($perPage);
    }
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        $allowedStatuses = ['draft', 'published', 'archived'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status '{$status}'. Allowed: " . implode(', ', $allowedStatuses));
        }
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getByStatus($status, $perPage);
    }
    public function getByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer.");
        }
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getByAuthor($userId, $perPage);
    }
    public function getRelated(int $articleId, int $limit = 5): Collection
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        if ($limit < 1 || $limit > 50) {
            throw new InvalidArgumentException("Limit must be between 1 and 50.");
        }
        $article = $this->repository->findById($articleId);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$articleId} not found.");
        }
        return $this->repository->getRelated($articleId, $limit);
    }
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (trim($query) === '') {
            throw new InvalidArgumentException("Search query cannot be empty.");
        }
        if (mb_strlen($query) < 2) {
            throw new InvalidArgumentException("Search query must be at least 2 characters.");
        }
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->search($query, $perPage);
    }
    public function store(CreateArticleDTO $dto): Article
    {
        if (empty($dto->title)) {
            throw new InvalidArgumentException("Article title is required.");
        }
        if (empty($dto->body)) {
            throw new InvalidArgumentException("Article body is required.");
        }
        if (empty($dto->slug)) {
            throw new InvalidArgumentException("Article slug is required.");
        }
        $existingArticle = $this->repository->findBySlug($dto->slug[array_key_first($dto->slug)]);
        if ($existingArticle) {
            throw new LogicException("An article with this slug already exists.");
        }
        return DB::transaction(function () use ($dto): Article {
            $article = $this->repository->create($dto);
            if (!$article) {
                throw new RuntimeException("Failed to create article.");
            }
            return $article->refresh();
        });
    }
    public function update(int $id, UpdateArticleDTO $dto): Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        if ($article->status === 'published' && isset($dto->status) && $dto->status === 'draft') {
            throw new LogicException("Cannot revert a published article to draft directly. Archive it first.");
        }
        if (isset($dto->slug)) {
            $existingArticle = $this->repository->findBySlug($dto->slug[array_key_first($dto->slug)]);
            if ($existingArticle && $existingArticle->id !== $id) {
                throw new LogicException("Another article with this slug already exists.");
            }
        }
        return DB::transaction(function () use ($id, $dto): Article {
            $article = $this->repository->update($id, $dto);
            if (!$article) {
                throw new RuntimeException("Failed to update article with ID {$id}.");
            }
            return $article->refresh();
        });
    }
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        if ($article->status === 'published') {
            throw new LogicException("Cannot delete a published article. Archive it first.");
        }
        $result = $this->repository->delete($id);
        if (!$result) {
            throw new RuntimeException("Failed to delete article with ID {$id}.");
        }
        return $result;
    }
    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        return DB::transaction(function () use ($article): bool {
            $result = $this->repository->forceDelete($article->id);
            if (!$result) {
                throw new RuntimeException("Failed to permanently delete article with ID {$article->id}.");
            }
            return $result;
        });
    }
    public function restore(int $id): Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->restore($id);
        if (!$article) {
            throw new RuntimeException("Failed to restore article with ID {$id}. It may not be in trash.");
        }
        return $article;
    }
    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException("Per page must be between 1 and 100.");
        }
        return $this->repository->getTrashed($perPage);
    }
    public function publish(int $id): Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        if ($article->status === 'published') {
            throw new LogicException("Article is already published.");
        }
        if (empty($article->body)) {
            throw new LogicException("Cannot publish an article without body content.");
        }
        if (empty($article->title)) {
            throw new LogicException("Cannot publish an article without a title.");
        }
        return $this->repository->publish($id);
    }
    public function archive(int $id): Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        if ($article->status === 'archived') {
            throw new LogicException("Article is already archived.");
        }
        if ($article->status === 'draft') {
            throw new LogicException("Cannot archive a draft article. Publish it first.");
        }
        return $this->repository->archive($id);
    }
    public function markAsDraft(int $id): Article
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Article ID must be a positive integer.");
        }
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new InvalidArgumentException("Article with ID {$id} not found.");
        }
        if ($article->status === 'draft') {
            throw new LogicException("Article is already a draft.");
        }
        if ($article->status === 'published') {
            throw new LogicException("Cannot revert a published article to draft. Archive it first.");
        }
        return $this->repository->markAsDraft($id);
    }
}
