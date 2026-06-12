<?php

declare(strict_types=1);

namespace Modules\Person\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Person', description: 'Cast and crew profiles with media and credits')]
#[OA\Schema(
    schema: 'Person',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'first_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'last_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'slug', type: 'string', example: 'jane-doe'),
        new OA\Property(property: 'biography', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'avatar', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'avatar_thumb', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'date_of_death', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'place_of_birth', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'non_binary', 'undisclosed']),
        new OA\Property(property: 'known_for_department', type: 'string', nullable: true, example: 'Acting'),
        new OA\Property(property: 'popularity', type: 'number', format: 'float', example: 8.5),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Credit',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'person_id', type: 'integer'),
        new OA\Property(property: 'creditable_type', type: 'string', example: 'movie'),
        new OA\Property(property: 'creditable_id', type: 'integer'),
        new OA\Property(property: 'role', type: 'string', enum: ['actor', 'director', 'writer', 'producer', 'executive_producer', 'composer', 'cinematographer', 'editor', 'crew', 'guest', 'narrator', 'other']),
        new OA\Property(property: 'character_name', type: 'string', nullable: true),
        new OA\Property(property: 'credited_as', type: 'string', nullable: true),
        new OA\Property(property: 'department', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StorePersonPayload',
    type: 'object',
    required: ['first_name', 'last_name', 'slug'],
    properties: [
        new OA\Property(property: 'first_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'last_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', type: 'string', description: 'Kebab-case, unique: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'biography', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true, description: 'Avatar image; multipart only'),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'date_of_death', type: 'string', format: 'date', nullable: true, description: 'Must be after date_of_birth'),
        new OA\Property(property: 'place_of_birth', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'non_binary', 'undisclosed']),
        new OA\Property(property: 'known_for_department', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'popularity', type: 'number', format: 'float', minimum: 0, default: 0),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
    ],
)]
#[OA\Schema(
    schema: 'UpdatePersonPayload',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'last_name', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'slug', type: 'string', description: 'Kebab-case, unique: ^[a-z0-9]+(?:-[a-z0-9]+)*$'),
        new OA\Property(property: 'biography', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true, description: 'Avatar image; multipart only'),
        new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'date_of_death', type: 'string', format: 'date', nullable: true, description: 'Must be after date_of_birth'),
        new OA\Property(property: 'place_of_birth', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female', 'non_binary', 'undisclosed']),
        new OA\Property(property: 'known_for_department', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'popularity', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'StoreCreditPayload',
    type: 'object',
    required: ['person_id', 'creditable_type', 'creditable_id', 'role'],
    properties: [
        new OA\Property(property: 'person_id', type: 'integer', description: 'Existing person id'),
        new OA\Property(property: 'creditable_type', type: 'string', description: 'Registered morph alias, e.g. movie, episode, article, person'),
        new OA\Property(property: 'creditable_id', type: 'integer', minimum: 1),
        new OA\Property(property: 'role', type: 'string', enum: ['actor', 'director', 'writer', 'producer', 'executive_producer', 'composer', 'cinematographer', 'editor', 'crew', 'guest', 'narrator', 'other']),
        new OA\Property(property: 'character_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'credited_as', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'department', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, maximum: 99999, default: 0),
    ],
)]
#[OA\Schema(
    schema: 'UpdateCreditPayload',
    type: 'object',
    properties: [
        new OA\Property(property: 'role', type: 'string', enum: ['actor', 'director', 'writer', 'producer', 'executive_producer', 'composer', 'cinematographer', 'editor', 'crew', 'guest', 'narrator', 'other']),
        new OA\Property(property: 'character_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'credited_as', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'department', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, maximum: 99999),
    ],
)]
#[OA\RequestBody(
    request: 'StorePersonRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/StorePersonPayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/StorePersonPayload')),
    ],
)]
#[OA\RequestBody(
    request: 'UpdatePersonRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/UpdatePersonPayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/UpdatePersonPayload')),
    ],
)]
#[OA\RequestBody(
    request: 'UploadPersonImageRequest',
    required: true,
    content: new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(
            required: ['image'],
            properties: [
                new OA\Property(property: 'image', type: 'string', format: 'binary'),
            ],
        ),
    ),
)]
#[OA\RequestBody(
    request: 'StoreCreditRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/StoreCreditPayload'),
)]
#[OA\RequestBody(
    request: 'UpdateCreditRequest',
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/UpdateCreditPayload'),
)]
#[OA\Response(
    response: 'PersonItem',
    description: 'Single person wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Person')]),
        ],
    ),
)]
#[OA\Response(
    response: 'PersonCreated',
    description: 'Person created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Person')]),
        ],
    ),
)]
#[OA\Response(
    response: 'PersonCollection',
    description: 'List of persons wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Person'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'PersonPage',
    description: 'Paginated persons with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Person')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'CreditItem',
    description: 'Single credit wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Credit')]),
        ],
    ),
)]
#[OA\Response(
    response: 'CreditCreated',
    description: 'Credit created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Credit')]),
        ],
    ),
)]
#[OA\Response(
    response: 'CreditCollection',
    description: 'List of credits wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Credit'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'CreditPage',
    description: 'Paginated credits with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Credit')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class PersonComponents
{
}
