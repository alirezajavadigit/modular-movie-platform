<?php

declare(strict_types=1);

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'Unauthorized',
    description: 'Missing, invalid, or expired bearer token',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/ErrorEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthorized')]),
        ],
    ),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'Authenticated but not permitted to perform this action',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/ErrorEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'message', type: 'string', example: 'You do not have permission to perform this action.')]),
        ],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Resource or endpoint not found',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/ErrorEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'message', type: 'string', example: 'Resource not found.')]),
        ],
    ),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Validation failed',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
)]
#[OA\Response(
    response: 'LegacyValidationError',
    description: 'Validation failed',
    content: new OA\JsonContent(ref: '#/components/schemas/LegacyStatusValidationError'),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Rate limit exceeded',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/ErrorEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Too many requests. Please slow down.'),
                new OA\Property(property: 'errors', properties: [new OA\Property(property: 'retry_after', type: 'integer', nullable: true, example: 60)], type: 'object'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'ServerError',
    description: 'Unexpected server error',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/ErrorEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred.')]),
        ],
    ),
)]
#[OA\Response(
    response: 'NoContent',
    description: 'Deleted; HTTP 204 is returned and any envelope body is discarded by the client',
)]
#[OA\Response(
    response: 'SuccessMessage',
    description: 'Acknowledged with the success envelope and null data',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'object', nullable: true, example: null)]),
        ],
    ),
)]
final class Responses
{
}
