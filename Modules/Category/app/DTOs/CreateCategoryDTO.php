<?php

namespace Modules\Category\DTOs;

readonly class CreateCategoryDTO
{
    public function __construct(
        public array   $name,
        public array   $slug,
        public ?array  $description,
        public ?int    $parentId,
        public bool    $isActive,
        public int     $order,
    ) {}
}
