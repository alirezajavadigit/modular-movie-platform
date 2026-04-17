<?php

namespace Modules\Discussion\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;

class DiscussionService implements DiscussionServiceInterface
{
    public function __construct(
        private readonly DiscussionRepositoryInterface $repository
    ) {}

    public function findById(int $id): ?Discussion
    {
        return $this->repository->findById($id);
    }

    public function getByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByDiscussionable($discussionableType, $discussionableId, $perPage);
    }

    public function getApprovedByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getApprovedByDiscussionable($discussionableType, $discussionableId, $perPage);
    }

    public function getReplies(int $parentId): Collection
    {
        return $this->repository->getReplies($parentId);
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByUser($userId, $perPage);
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPending($perPage);
    }

    public function store(CreateDiscussionDTO $dto): Discussion
    {
        return $this->repository->create([
            'user_id'             => $dto->userId,
            'discussionable_id'   => $dto->discussionableId,
            'discussionable_type' => $dto->discussionableType,
            'body'                => $dto->body,
            'parent_id'           => $dto->parentId,
            'status'              => $dto->status->value,
            'ip_address'          => $dto->ipAddress,
        ]);
    }

    public function update(Discussion $discussion, UpdateDiscussionDTO $dto): bool
    {
        $data = array_filter([
            'body'   => $dto->body,
            'status' => $dto->status?->value,
        ], fn ($value) => ! is_null($value));

        if (empty($data)) {
            return true;
        }

        return $this->repository->update($discussion, $data);
    }

    public function delete(Discussion $discussion): bool
    {
        return $this->repository->delete($discussion);
    }

    public function forceDelete(Discussion $discussion): bool
    {
        return $this->repository->forceDelete($discussion);
    }

    public function restore(Discussion $discussion): bool
    {
        return $this->repository->restore($discussion);
    }

    public function approve(Discussion $discussion): bool
    {
        return $this->repository->update($discussion, [
            'status' => DiscussionStatus::APPROVED->value,
        ]);
    }

    public function reject(Discussion $discussion): bool
    {
        return $this->repository->update($discussion, [
            'status' => DiscussionStatus::REJECTED->value,
        ]);
    }

    public function markAsPending(Discussion $discussion): bool
    {
        return $this->repository->update($discussion, [
            'status' => DiscussionStatus::PENDING->value,
        ]);
    }

    public function discussionsCount(string $discussionableType, int $discussionableId): int
    {
        return $this->repository->countByDiscussionable($discussionableType, $discussionableId);
    }

    public function approvedDiscussionsCount(string $discussionableType, int $discussionableId): int
    {
        return $this->repository->countApprovedByDiscussionable($discussionableType, $discussionableId);
    }

    public function hasDiscussions(string $discussionableType, int $discussionableId): bool
    {
        return $this->discussionsCount($discussionableType, $discussionableId) > 0;
    }
}
