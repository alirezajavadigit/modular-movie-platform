<?php

namespace Modules\Discussion\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Models\Discussion;

interface DiscussionServiceInterface
{
    public function findById(int $id): ?Discussion;

    public function getByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator;

    public function getApprovedByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator;

    public function getReplies(int $parentId): Collection;

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getRejected(int $perPage = 15): LengthAwarePaginator;

    public function getApproved(int $perPage = 15): LengthAwarePaginator;

    public function getPending(int $perPage = 15): LengthAwarePaginator;

    public function store(CreateDiscussionDTO $dto): Discussion;

    public function update(Discussion $discussion, UpdateDiscussionDTO $dto): bool;

    public function delete(Discussion $discussion): bool;

    public function forceDelete(Discussion $discussion): bool;

    public function restore(Discussion $discussion): bool;

    public function approve(Discussion $discussion): bool;

    public function reject(Discussion $discussion): bool;

    public function markAsPending(Discussion $discussion): bool;

    public function discussionsCount(string $discussionableType, int $discussionableId): int;

    public function approvedDiscussionsCount(string $discussionableType, int $discussionableId): int;

    public function hasDiscussions(string $discussionableType, int $discussionableId): bool;

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
