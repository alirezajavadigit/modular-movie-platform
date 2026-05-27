<?php

namespace Modules\Discussion\DTOs;

use Modules\Discussion\Enums\DiscussionStatus;

readonly class UpdateDiscussionDTO
{
    public function __construct(
        public ?string $body = null,
        public ?DiscussionStatus $status = null,
    ) {}
}
