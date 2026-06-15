<?php

declare(strict_types=1);

namespace Modules\Auth\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Registration, JWT session management, OTP password recovery, and Google OAuth')]
#[OA\Schema(
    schema: 'AuthUser',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'phone_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'AuthSessionData',
    type: 'object',
    required: ['status', 'data'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'success'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                new OA\Property(property: 'user', ref: '#/components/schemas/AuthUser'),
            ],
        ),
    ],
)]
#[OA\RequestBody(
    request: 'RegisterRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'password', 'password_confirmation'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, description: 'Required when phone is absent'),
            new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Required when email is absent'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', minLength: 8),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'LoginRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['identifier', 'password'],
        properties: [
            new OA\Property(property: 'identifier', type: 'string', description: 'Email address or phone number'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'ForgotPasswordRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['identifier'],
        properties: [
            new OA\Property(property: 'identifier', type: 'string', description: 'Email address or phone number; selects the OTP channel'),
        ],
    ),
)]
#[OA\RequestBody(
    request: 'ChangePasswordRequest',
    required: true,
    content: new OA\JsonContent(
        required: ['current_password', 'new_password', 'new_password_confirmation'],
        properties: [
            new OA\Property(property: 'current_password', type: 'string', format: 'password'),
            new OA\Property(property: 'new_password', type: 'string', format: 'password', minLength: 8),
            new OA\Property(property: 'new_password_confirmation', type: 'string', format: 'password', minLength: 8),
        ],
    ),
)]
#[OA\Response(
    response: 'AuthSession',
    description: 'JWT and the authenticated user in the Auth module status envelope',
    content: new OA\JsonContent(ref: '#/components/schemas/AuthSessionData'),
)]
#[OA\Response(
    response: 'AuthSessionCreated',
    description: 'Account created; JWT and user in the Auth module status envelope',
    content: new OA\JsonContent(ref: '#/components/schemas/AuthSessionData'),
)]
#[OA\Response(
    response: 'AuthToken',
    description: 'Fresh JWT in the Auth module status envelope',
    content: new OA\JsonContent(
        required: ['status', 'data'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'token', type: 'string'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'AuthMessage',
    description: 'Acknowledgement message in the Auth module status envelope',
    content: new OA\JsonContent(
        required: ['status', 'data'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'message', type: 'string'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'AuthRedirect',
    description: 'Google consent screen URL in the Auth module status envelope',
    content: new OA\JsonContent(
        required: ['status', 'data'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'redirect_url', type: 'string', format: 'uri'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'AuthProfile',
    description: 'Authenticated user in the Auth module status envelope',
    content: new OA\JsonContent(
        required: ['status', 'data'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', ref: '#/components/schemas/AuthUser'),
        ],
    ),
)]
final class AuthComponents
{
}
