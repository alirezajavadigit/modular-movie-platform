<?php

declare(strict_types=1);

namespace Modules\Notification\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notification', description: 'Multi-channel notifications managed per notifiable entity')]
#[OA\Schema(
    schema: 'Notification',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'notifiable_id', type: 'integer'),
        new OA\Property(property: 'notifiable_type', type: 'string', example: 'user'),
        new OA\Property(property: 'type', type: 'string', example: 'user.welcome'),
        new OA\Property(property: 'channel', type: 'string', enum: ['database', 'email', 'sms', 'push']),
        new OA\Property(property: 'channel_label', type: 'string', example: 'Database'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
        new OA\Property(property: 'is_read', type: 'boolean'),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'sent_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\RequestBody(
    request: 'StoreNotificationRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['notifiable_type', 'notifiable_id', 'type', 'channel'],
        properties: [
            new OA\Property(property: 'notifiable_type', type: 'string', enum: ['user']),
            new OA\Property(property: 'notifiable_id', type: 'integer', minimum: 1),
            new OA\Property(property: 'type', type: 'string', enum: ['user.welcome', 'user.password_reset', 'order.placed', 'order.status_changed', 'comment.received'], description: 'Registered notification type; extendable via module config'),
            new OA\Property(property: 'channel', type: 'string', enum: ['database', 'email', 'sms', 'push']),
            new OA\Property(property: 'data', type: 'object', nullable: true),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'UpdateNotificationRequest',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'type', type: 'string', enum: ['user.welcome', 'user.password_reset', 'order.placed', 'order.status_changed', 'comment.received']),
            new OA\Property(property: 'channel', type: 'string', enum: ['database', 'email', 'sms', 'push']),
            new OA\Property(property: 'data', type: 'object'),
        ],
    ),
)]
#[OA\Response(
    response: 'NotificationItem',
    description: 'Single notification wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Notification')]),
        ],
    ),
)]
#[OA\Response(
    response: 'NotificationCreated',
    description: 'Notification created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Notification')]),
        ],
    ),
)]
#[OA\Response(
    response: 'NotificationPage',
    description: 'Paginated notifications with fractal pagination meta',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Notification')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'NotificationTypes',
    description: 'Registered notification types keyed by identifier',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'label', type: 'string'),
                            new OA\Property(property: 'channels', type: 'array', items: new OA\Items(type: 'string')),
                        ],
                    ),
                    example: ['user.welcome' => ['label' => 'Welcome', 'channels' => ['database', 'email']]],
                ),
            ]),
        ],
    ),
)]
final class NotificationComponents
{
}
