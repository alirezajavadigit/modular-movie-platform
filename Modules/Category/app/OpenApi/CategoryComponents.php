<?php

declare(strict_types=1);

namespace Modules\Category\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Category', description: 'Hierarchical, translatable content categories')]
#[OA\Schema(
    schema: 'Category',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'order', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StoreCategoryPayload',
    type: 'object',
    required: ['name', 'slug'],
    properties: [
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Existing category id'),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, maximum: 99999, default: 0),
    ],
)]
#[OA\Schema(
    schema: 'UpdateCategoryPayload',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Existing category id'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, maximum: 99999),
    ],
)]
#[OA\RequestBody(
    request: 'StoreCategoryRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/StoreCategoryPayload'),
)]
#[OA\RequestBody(
    request: 'UpdateCategoryRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/UpdateCategoryPayload'),
)]
#[OA\Response(
    response: 'CategoryItem',
    description: 'Single category wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Category')]),
        ],
    ),
)]
#[OA\Response(
    response: 'CategoryCreated',
    description: 'Category created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Category')]),
        ],
    ),
)]
#[OA\Response(
    response: 'CategoryCollection',
    description: 'List of categories wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'CategoryPage',
    description: 'Paginated categories with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class CategoryComponents
{
}
