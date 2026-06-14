<?php

declare(strict_types=1);

namespace Modules\Like\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Like', description: 'User likes for movies, episodes, articles, and persons')]
#[OA\Schema(
    schema: 'Like',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'likeable_type', type: 'string', example: 'Movie'),
        new OA\Property(property: 'likeable_id', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreLikeRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['likeable_type', 'likeable_id'],
        properties: [
            new OA\Property(property: 'likeable_type', type: 'string', enum: ['movie', 'episode', 'article', 'person']),
            new OA\Property(property: 'likeable_id', type: 'integer', minimum: 1),
        ],
    ),
)]
#[OA\Response(
    response: 'LikeItem',
    description: 'Existing like wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Like')]),
        ],
    ),
)]
#[OA\Response(
    response: 'LikeCreated',
    description: 'Like created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Like')]),
        ],
    ),
)]
#[OA\Response(
    response: 'LikePage',
    description: 'Paginated likes with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Like')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'LikeToggle',
    description: 'Toggle outcome with the current like count',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'liked', type: 'boolean'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]),
            ]),
        ],
    ),
)]
final class LikeComponents
{
}
