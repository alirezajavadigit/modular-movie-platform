<?php

namespace Modules\Discussion\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Discussion\Models\Discussion;

interface DiscussionRepositoryInterface
{
    public function findById(int $id): ?Discussion;

    public function getByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator;

    public function getApprovedByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator;

    public function getReplies(int $parentId): Collection;

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getRejected(int $perPage = 15): LengthAwarePaginator;

    public function getApproved(int $perPage = 15): LengthAwarePaginator;
    
    public function getPending(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Discussion;

    public function update(Discussion $discussion, array $data): bool;

    public function delete(Discussion $discussion): bool;

    public function forceDelete(Discussion $discussion): bool;

    public function restore(Discussion $discussion): bool;

    public function countByDiscussionable(string $discussionableType, int $discussionableId): int;

    public function countApprovedByDiscussionable(string $discussionableType, int $discussionableId): int;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
