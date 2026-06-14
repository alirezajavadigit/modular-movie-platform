<?php

declare(strict_types=1);

namespace Modules\Payment\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payment', description: 'Gateway payments, callbacks, and verification')]
#[OA\Schema(
    schema: 'Payment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'payable_id', type: 'integer'),
        new OA\Property(property: 'payable_type', type: 'string', example: 'subscription'),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 49.99),
        new OA\Property(property: 'driver', type: 'string', example: 'stripe'),
        new OA\Property(property: 'transaction_id', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'success', 'failed', 'canceled', 'refunded']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'UpdatePaymentRequest',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'transaction_id', type: 'string', nullable: true, maxLength: 255),
            new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['pending', 'success', 'failed', 'canceled', 'refunded']),
        ],
    ),
)]
#[OA\Response(
    response: 'PaymentItem',
    description: 'Single payment wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Payment')]),
        ],
    ),
)]
#[OA\Response(
    response: 'PaymentCollection',
    description: 'List of payments wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Payment'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'PaymentPage',
    description: 'Paginated payments with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Payment')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
final class PaymentComponents
{
}
