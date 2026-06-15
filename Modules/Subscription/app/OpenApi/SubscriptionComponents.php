<?php

declare(strict_types=1);

namespace Modules\Subscription\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Subscription', description: 'Subscription plans and gateway-backed user subscriptions')]
#[OA\Schema(
    schema: 'SubscriptionPlan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Premium Monthly'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 9.99),
        new OA\Property(property: 'duration_days', type: 'integer', example: 30),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Subscription',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'plan_id', type: 'integer'),
        new OA\Property(property: 'payment_id', type: 'integer', nullable: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'active', 'expired', 'canceled']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreSubscriptionPlanRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'price', 'duration_days'],
        properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000),
            new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
            new OA\Property(property: 'duration_days', type: 'integer', minimum: 1),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'UpdateSubscriptionPlanRequest',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000),
            new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
            new OA\Property(property: 'duration_days', type: 'integer', minimum: 1),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'StoreSubscriptionRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['plan_id', 'driver'],
        properties: [
            new OA\Property(property: 'plan_id', type: 'integer', minimum: 1),
            new OA\Property(property: 'driver', type: 'string', maxLength: 100, example: 'stripe'),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionPlanItem',
    description: 'Single subscription plan wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionPlan')]),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionPlanCreated',
    description: 'Subscription plan created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionPlan')]),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionPlanPage',
    description: 'Paginated subscription plans with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SubscriptionPlan')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionItem',
    description: 'Single subscription wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Subscription')]),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionPage',
    description: 'Paginated subscriptions with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subscription')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'SubscriptionPaymentUrl',
    description: 'Gateway checkout URL for the pending subscription',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'payment_url', type: 'string', format: 'uri'),
                ]),
            ]),
        ],
    ),
)]
final class SubscriptionComponents
{
}
