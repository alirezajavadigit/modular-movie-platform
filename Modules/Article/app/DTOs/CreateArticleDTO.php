<?php

namespace Modules\Article\DTOs;

readonly class CreateArticleDTO
{
    public function __construct(
        public int     $userId,
        public array   $title,
        public array   $slug,
        public ?array  $summary,
        public array   $body,
        public string  $status,
        public ?int    $readTime,
        public bool    $isFeatured,
        public bool    $allowComments,
        public ?string $publishedAt,
    ) {}
}
