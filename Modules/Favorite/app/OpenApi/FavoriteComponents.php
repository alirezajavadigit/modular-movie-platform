<?php

declare(strict_types=1);

namespace Modules\Favorite\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Favorite', description: 'User favorites for movies, episodes, articles, and persons')]
#[OA\Schema(
    schema: 'Favorite',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'favoritable_type', type: 'string', example: 'Movie'),
        new OA\Property(property: 'favoritable_id', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreFavoriteRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['favoriteable_type', 'favoriteable_id'],
        properties: [
            new OA\Property(property: 'favoriteable_type', type: 'string', enum: ['movie', 'episode', 'article', 'person']),
            new OA\Property(property: 'favoriteable_id', type: 'integer', minimum: 1),
        ],
    ),
)]
#[OA\Response(
    response: 'FavoriteItem',
    description: 'Existing favorite wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Favorite')]),
        ],
    ),
)]
#[OA\Response(
    response: 'FavoriteCreated',
    description: 'Favorite created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Favorite')]),
        ],
    ),
)]
#[OA\Response(
    response: 'FavoritePage',
    description: 'Paginated favorites with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Favorite')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'FavoriteToggle',
    description: 'Toggle outcome with the current favorite count',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'favorited', type: 'boolean'),
                    new OA\Property(property: 'count', type: 'integer'),
                ]),
            ]),
        ],
    ),
)]
final class FavoriteComponents
{
}
