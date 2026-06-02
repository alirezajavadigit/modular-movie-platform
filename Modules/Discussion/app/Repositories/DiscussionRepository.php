<?php

namespace Modules\Discussion\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\Models\Discussion;

final class DiscussionRepository implements DiscussionRepositoryInterface
{
    public function __construct(
        private readonly Discussion $model,
    ) {}

    public function findById(int $id): ?Discussion
    {
        return $this->model->newQuery()->find($id);
    }

    public function getByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forDiscussionable($discussionableType, $discussionableId)
            ->parents()
            ->with(['user', 'approvedReplies.user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getApprovedByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forDiscussionable($discussionableType, $discussionableId)
            ->parents()
            ->approved()
            ->with(['user', 'approvedReplies.user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getReplies(int $parentId): Collection
    {
        return $this->model->newQuery()
            ->where('parent_id', $parentId)
            ->approved()
            ->with('user')
            ->oldest()
            ->get();
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['discussionable', 'parent'])
            ->latest()
            ->paginate($perPage);
    }

    public function getRejected(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->rejected()
            ->with(['user', 'discussionable'])
            ->latest()
            ->paginate($perPage);
    }

    public function getApproved(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->approved()
            ->with(['user', 'discussionable'])
            ->latest()
            ->paginate($perPage);
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->pending()
            ->with(['user', 'discussionable'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Discussion
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Discussion $discussion, array $data): bool
    {
        return $discussion->update($data);
    }

    public function delete(Discussion $discussion): bool
    {
        return (bool) $discussion->delete();
    }

    public function forceDelete(Discussion $discussion): bool
    {
        return (bool) $discussion->forceDelete();
    }

    public function restore(Discussion $discussion): bool
    {
        return (bool) $discussion->restore();
    }

    public function countByDiscussionable(string $discussionableType, int $discussionableId): int
    {
        return $this->model->newQuery()
            ->forDiscussionable($discussionableType, $discussionableId)
            ->parents()
            ->count();
    }

    public function countApprovedByDiscussionable(string $discussionableType, int $discussionableId): int
    {
        return $this->model->newQuery()
            ->forDiscussionable($discussionableType, $discussionableId)
            ->parents()
            ->approved()
            ->count();
    }
}
