<?php

declare(strict_types=1);

namespace Modules\Tag\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tag', description: 'Translatable content tags with usage statistics')]
#[OA\Schema(
    schema: 'TagResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: '#FF5733'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'articles_count', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StoreTagPayload',
    type: 'object',
    required: ['name', 'slug'],
    properties: [
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'color', type: 'string', nullable: true, description: 'Hex color: #RGB or #RRGGBB', example: '#FF5733'),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
    ],
)]
#[OA\Schema(
    schema: 'UpdateTagPayload',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', ref: '#/components/schemas/TranslationMap', description: 'Kebab-case per locale: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'color', type: 'string', nullable: true, description: 'Hex color: #RGB or #RRGGBB'),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
)]
#[OA\RequestBody(
    request: 'StoreTagRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/StoreTagPayload'),
)]
#[OA\RequestBody(
    request: 'UpdateTagRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/UpdateTagPayload'),
)]
#[OA\Response(
    response: 'TagItem',
    description: 'Single tag wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TagResource')]),
        ],
    ),
)]
#[OA\Response(
    response: 'TagCreated',
    description: 'Tag created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TagResource')]),
        ],
    ),
)]
#[OA\Response(
    response: 'TagCollection',
    description: 'List of tags wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TagResource'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'TagPage',
    description: 'Paginated tags with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TagResource')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class TagComponents
{
}
