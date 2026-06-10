<?php

declare(strict_types=1);

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuccessEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Operation successful'),
    ],
)]
#[OA\Schema(
    schema: 'ErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Something went wrong'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorEnvelope',
    type: 'object',
    required: ['success', 'message', 'errors'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
            example: ['title' => ['The title field is required.']],
        ),
    ],
)]
#[OA\Schema(
    schema: 'LegacyStatusValidationError',
    type: 'object',
    required: ['status', 'message'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'error'),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 42),
        new OA\Property(property: 'count', type: 'integer', example: 15),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'total_pages', type: 'integer', example: 3),
        new OA\Property(
            property: 'links',
            type: 'object',
            properties: [
                new OA\Property(property: 'previous', type: 'string', nullable: true),
                new OA\Property(property: 'next', type: 'string', nullable: true),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'TranslationMap',
    type: 'object',
    description: 'Locale-keyed translations, e.g. {"en": "...", "fa": "..."}.',
    additionalProperties: new OA\AdditionalProperties(type: 'string'),
    example: ['en' => 'Example', 'fa' => 'نمونه'],
)]
#[OA\Schema(
    schema: 'NullableTranslationMap',
    type: 'object',
    nullable: true,
    additionalProperties: new OA\AdditionalProperties(type: 'string', nullable: true),
    example: ['en' => 'Example'],
)]
final class Envelopes
{
}
