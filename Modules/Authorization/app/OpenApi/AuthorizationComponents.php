<?php

declare(strict_types=1);

namespace Modules\Authorization\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authorization', description: 'Roles, permissions, and their assignment to users')]
#[OA\Schema(
    schema: 'Role',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'editor'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'api'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Permission',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'movie.create'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'api'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreRoleRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'guard_name', type: 'string', maxLength: 255, default: 'api'),
            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), description: 'Existing permission names'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'UpdateRoleRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'guard_name', type: 'string', maxLength: 255),
            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), description: 'Existing permission names'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'SyncRolePermissionsRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['permissions'],
        properties: [
            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), description: 'Full replacement set of existing permission names; may be empty'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'RoleNamesRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['roles'],
        properties: [
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), minItems: 1, description: 'Existing role names'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'SyncRolesRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['roles'],
        properties: [
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Full replacement set of existing role names; may be empty'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'PermissionNamesRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['permissions'],
        properties: [
            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), minItems: 1, description: 'Existing permission names'),
        ],
    ),
)]
#[OA\Response(
    response: 'RoleItem',
    description: 'Single role wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Role')]),
        ],
    ),
)]
#[OA\Response(
    response: 'RoleCreated',
    description: 'Role created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Role')]),
        ],
    ),
)]
#[OA\Response(
    response: 'RoleCollection',
    description: 'List of roles wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'PermissionCollection',
    description: 'List of permissions wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission'))]),
        ],
    ),
)]
final class AuthorizationComponents
{
}
