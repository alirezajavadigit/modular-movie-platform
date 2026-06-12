<?php

declare(strict_types=1);

namespace Modules\Discussion\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Discussion', description: 'Moderated comments and threaded replies on movies, episodes, and articles')]
#[OA\Schema(
    schema: 'Discussion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'body', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Approved'),
        new OA\Property(property: 'is_reply', type: 'boolean'),
        new OA\Property(property: 'is_approved', type: 'boolean'),
        new OA\Property(property: 'ip_address', type: 'string', nullable: true),
        new OA\Property(property: 'discussionable_type', type: 'string', example: 'movie'),
        new OA\Property(property: 'discussionable_id', type: 'integer'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreDiscussionRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['discussionable_id', 'discussionable_type', 'body'],
        properties: [
            new OA\Property(property: 'discussionable_id', type: 'integer', minimum: 1),
            new OA\Property(property: 'discussionable_type', type: 'string', enum: ['movie', 'episode', 'article']),
            new OA\Property(property: 'body', type: 'string', minLength: 3, maxLength: 5000),
            new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Existing discussion id when replying'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'UpdateDiscussionRequest',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'body', type: 'string', minLength: 3, maxLength: 5000),
            new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected']),
        ],
    ),
)]
#[OA\Response(
    response: 'DiscussionItem',
    description: 'Single discussion wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Discussion')]),
        ],
    ),
)]
#[OA\Response(
    response: 'DiscussionCreated',
    description: 'Discussion created; status depends on the auto-approve setting',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Discussion')]),
        ],
    ),
)]
#[OA\Response(
    response: 'DiscussionCollection',
    description: 'List of discussions wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Discussion'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'DiscussionPage',
    description: 'Paginated discussions with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Discussion')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class DiscussionComponents
{
}
