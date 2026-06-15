<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Modular Movie Platform API',
    description: 'REST API for the Modular Movie Platform. All endpoints are versioned under /api/v1 and grouped by module. Protected endpoints require a JWT bearer token obtained from the Auth module.',
    contact: new OA\Contact(name: 'API Support', email: 'alirezajavadigit@gmail.com'),
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Primary application server',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT issued by POST /api/v1/auth/login or POST /api/v1/auth/register. Send as: Authorization: Bearer {token}.',
)]
final class ApiDefinition
{
}
