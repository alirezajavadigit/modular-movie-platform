<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Person\Contracts\CreditServiceInterface;
use Modules\Person\Http\Resources\Transformers\CreditTransformer;
use OpenApi\Attributes as OA;

class CreditQueryController extends Controller
{
    public function __construct(
        private readonly CreditServiceInterface $service,
        private readonly CreditTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/credits/{creditableType}/{creditableId}/cast',
        operationId: 'credit.public.cast',
        summary: 'List the cast of a creditable resource',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'creditableType', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'movie'),
            new OA\Parameter(name: 'creditableId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditCollection')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function cast(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getCastFor($creditableType, $creditableId);

        return ApiResponse::fractalCollection($credits, $this->transformer, __('person::messages.cast'));
    }

    #[OA\Get(
        path: '/api/v1/credits/{creditableType}/{creditableId}/crew',
        operationId: 'credit.public.crew',
        summary: 'List the crew of a creditable resource',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'creditableType', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'movie'),
            new OA\Parameter(name: 'creditableId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditCollection')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function crew(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getCrewFor($creditableType, $creditableId);

        return ApiResponse::fractalCollection($credits, $this->transformer, __('person::messages.crew'));
    }

    #[OA\Get(
        path: '/api/v1/credits/{creditableType}/{creditableId}',
        operationId: 'credit.public.forCreditable',
        summary: 'List all credits of a creditable resource',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'creditableType', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'movie'),
            new OA\Parameter(name: 'creditableId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forCreditable(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getByCreditable($creditableType, $creditableId);

        return ApiResponse::paginated($credits, $this->transformer, __('person::messages.credits_index'));
    }
}
