<?php

namespace Modules\Discussion\DTOs;

use Modules\Discussion\Enums\DiscussionStatus;

readonly class CreateDiscussionDTO
{
    public function __construct(
        public int $userId,
        public int $discussionableId,
        public string $discussionableType,
        public string $body,
        public ?int $parentId = null,
        public DiscussionStatus $status = DiscussionStatus::PENDING,
        public ?string $ipAddress = null,
    ) {}
}
