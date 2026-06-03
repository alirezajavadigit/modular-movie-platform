<?php

namespace Modules\Discussion\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;

final class DiscussionService implements DiscussionServiceInterface
{
    public function __construct(
        private readonly DiscussionRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Discussion
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Discussion ID must be a positive integer.');
        }

        return $this->repository->findById($id);
    }

    public function getByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByDiscussionable($discussionableType, $discussionableId, $this->guardPerPage($perPage));
    }

    private function discussionableTypesResolver(string $discussionableType): string
    {
        $mapping = config('discussion-module.discussionable_types', []);
        if (!array_key_exists($discussionableType, $mapping)) {
            throw new InvalidArgumentException('Invalid discussionable type: ' . $discussionableType);
        }

        return $mapping[$discussionableType];
    }
    public function getApprovedByDiscussionable(string $discussionableType, int $discussionableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getApprovedByDiscussionable($discussionableType, $discussionableId, $this->guardPerPage($perPage));
    }

    public function getReplies(int $parentId): Collection
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent ID must be a positive integer.');
        }

        return $this->repository->getReplies($parentId);
    }

    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return $this->repository->getByUser($userId, $this->guardPerPage($perPage));
    }

    public function getRejected(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getRejected($this->guardPerPage($perPage));
    }

    public function getApproved(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getApproved($this->guardPerPage($perPage));
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPending($this->guardPerPage($perPage));
    }

    public function store(CreateDiscussionDTO $dto): Discussion
    {
        return DB::transaction(fn(): Discussion => $this->repository->create([
            'user_id'             => $dto->userId,
            'discussionable_id'   => $dto->discussionableId,
            'discussionable_type' => $dto->discussionableType,
            'body'                => $dto->body,
            'parent_id'           => $dto->parentId,
            'status'              => $dto->status->value,
            'ip_address'          => $dto->ipAddress,
        ]));
    }

    public function update(Discussion $discussion, UpdateDiscussionDTO $dto): bool
    {
        $data = array_filter([
            'body'   => $dto->body,
            'status' => $dto->status?->value,
        ], fn($value) => ! is_null($value));

        if (empty($data)) {
            return true;
        }

        return DB::transaction(fn(): bool => $this->repository->update($discussion, $data));
    }

    public function delete(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->delete($discussion));
    }

    public function forceDelete(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->forceDelete($discussion));
    }

    public function restore(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->restore($discussion));
    }

    public function approve(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->update($discussion, [
            'status' => DiscussionStatus::APPROVED->value,
        ]));
    }

    public function reject(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->update($discussion, [
            'status' => DiscussionStatus::REJECTED->value,
        ]));
    }

    public function markAsPending(Discussion $discussion): bool
    {
        return DB::transaction(fn(): bool => $this->repository->update($discussion, [
            'status' => DiscussionStatus::PENDING->value,
        ]));
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

    private function guardPerPage(int $perPage): int
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $perPage;
    }
}
