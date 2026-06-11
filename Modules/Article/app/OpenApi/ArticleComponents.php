<?php

declare(strict_types=1);

namespace Modules\Article\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Article', description: 'Editorial content with translatable fields, statuses, and soft deletes')]
#[OA\Schema(
    schema: 'Article',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 7),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'summary', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'body', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
        new OA\Property(property: 'read_time', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'allow_comments', type: 'boolean'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StoreArticlePayload',
    type: 'object',
    required: ['title', 'slug', 'body'],
    properties: [
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'summary', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'body', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], default: 'draft'),
        new OA\Property(property: 'read_time', type: 'integer', nullable: true, minimum: 1, maximum: 999),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'allow_comments', type: 'boolean'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true, description: 'Must be now or later'),
        new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1),
        new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ],
)]
#[OA\Schema(
    schema: 'UpdateArticlePayload',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'summary', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'body', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
        new OA\Property(property: 'read_time', type: 'integer', nullable: true, minimum: 1, maximum: 999),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'allow_comments', type: 'boolean'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ],
)]
#[OA\RequestBody(
    request: 'StoreArticleRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/StoreArticlePayload'),
)]
#[OA\RequestBody(
    request: 'UpdateArticleRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/UpdateArticlePayload'),
)]
#[OA\Response(
    response: 'ArticleItem',
    description: 'Single article wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Article')]),
        ],
    ),
)]
#[OA\Response(
    response: 'ArticleCreated',
    description: 'Article created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Article')]),
        ],
    ),
)]
#[OA\Response(
    response: 'ArticleCollection',
    description: 'List of articles wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Article'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'ArticlePage',
    description: 'Paginated articles with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Article')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class ArticleComponents
{
}
