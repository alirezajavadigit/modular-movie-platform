<?php

declare(strict_types=1);

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'Page',
    name: 'page',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
)]
#[OA\Parameter(
    parameter: 'PerPage',
    name: 'per_page',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 15),
)]
#[OA\Parameter(
    parameter: 'SearchQuery',
    name: 'q',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string'),
)]
final class Parameters
{
}
