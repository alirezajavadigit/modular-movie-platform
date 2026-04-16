<?php

namespace Modules\Article\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Models\Article;

final class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly Article $model,
    ) {}

    public function findById(int $id): ?Article
    {
        return $this->model->newQuery()->find($id);
    }
    
    public function findBySlug(string $slug): ?Article
    {
        return $this->model->newQuery()
            ->where("slug->" . app()->getLocale(), $slug)
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

    public function getPublished(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function getDrafts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('status', 'draft')
            ->latest()
            ->paginate($perPage);
    }

    public function getArchived(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('status', 'archived')
            ->latest()
            ->paginate($perPage);
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->latest()
            ->paginate($perPage);
    }

    public function getByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getRelated(int $articleId, int $limit = 5): Collection
    {
        $article = $this->model->newQuery()->findOrFail($articleId);

        return $this->model->newQuery()
            ->where('id', '!=', $articleId)
            ->where('status', 'published')
            ->whereHas('categories', function ($query) use ($article) {
                $query->whereIn('categories.id', $article->categories()->pluck('categories.id'));
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('body', 'LIKE', "%{$query}%")
                    ->orWhere('summary', 'LIKE', "%{$query}%");
            })
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function create(CreateArticleDTO $dto): Article
    {
        return $this->model->newQuery()->create([
            'user_id' => $dto->userId,
            'title' => $dto->title,
            'slug' => $dto->slug,
            'summary' => $dto->summary,
            'body' => $dto->body,
            'status' => $dto->status,
            'read_time' => $dto->readTime,
            'is_featured' => $dto->isFeatured,
            'allow_comments' => $dto->allowComments,
            'published_at' => $dto->publishedAt,
        ]);
    }

    public function update(int $id, UpdateArticleDTO $dto): Article
    {
        $article = $this->model->newQuery()->findOrFail($id);

        $article->update([
            'title' => $dto->title,
            'slug' => $dto->slug,
            'summary' => $dto->summary,
            'body' => $dto->body,
            'status' => $dto->status,
            'read_time' => $dto->readTime,
            'is_featured' => $dto->isFeatured,
            'allow_comments' => $dto->allowComments,
            'published_at' => $dto->publishedAt,
        ]);

        return $article->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Article
    {
        $article = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $article->restore();

        return $article->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    public function publish(int $id): Article
    {
        $article = $this->model->newQuery()->findOrFail($id);
        $article->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $article->refresh();
    }

    public function archive(int $id): Article
    {
        $article = $this->model->newQuery()->findOrFail($id);
        $article->update(['status' => 'archived']);

        return $article->refresh();
    }

    public function markAsDraft(int $id): Article
    {
        $article = $this->model->newQuery()->findOrFail($id);
        $article->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $article->refresh();
    }

    public function syncCategories(int $id, array $categoryIds): void
    {
        $article = $this->model->newQuery()->findOrFail($id);
        $article->categories()->sync($categoryIds);
    }

    public function syncTags(int $id, array $tagIds): void
    {
        $article = $this->model->newQuery()->findOrFail($id);
        $article->tags()->sync($tagIds);
    }
}
