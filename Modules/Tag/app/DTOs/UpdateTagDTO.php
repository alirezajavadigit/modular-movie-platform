<?php

namespace Modules\Tag\DTOs;

readonly class UpdateTagDTO
{
    public function __construct(
        public ?array  $name,
        public ?array  $slug,
        public ?array  $description,
        public ?string $color,
        public ?bool   $isActive,
    ) {}
}
