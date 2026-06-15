<?php

declare(strict_types=1);

namespace Modules\User\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'User', description: 'Administrative user account management')]
#[OA\Schema(
    schema: 'ManagedUser',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['editor']),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'phone_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreUserRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'password'],
        properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, maxLength: 255, description: 'Unique; required when phone is absent'),
            new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 255, description: 'Unique; required when email is absent'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, maxLength: 255),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Existing role names'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'UpdateUserRequest',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, maxLength: 255, description: 'Unique among other users'),
            new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 255, description: 'Unique among other users'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, maxLength: 255),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Full replacement set of existing role names'),
        ],
    ),
)]
#[OA\Response(
    response: 'UserItem',
    description: 'Single user wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ManagedUser')]),
        ],
    ),
)]
#[OA\Response(
    response: 'UserCreated',
    description: 'User created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ManagedUser')]),
        ],
    ),
)]
#[OA\Response(
    response: 'UserPage',
    description: 'Paginated users with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ManagedUser')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class UserComponents
{
}
