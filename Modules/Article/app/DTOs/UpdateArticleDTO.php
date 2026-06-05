<?php

namespace Modules\Article\DTOs;

readonly class UpdateArticleDTO
{
    public function __construct(
        public ?array  $title,
        public ?array  $slug,
        public ?array  $summary,
        public ?array  $body,
        public ?string $status,
        public ?int    $readTime,
        public ?bool   $isFeatured,
        public ?bool   $allowComments,
        public ?string $publishedAt,
    ) {}
}
