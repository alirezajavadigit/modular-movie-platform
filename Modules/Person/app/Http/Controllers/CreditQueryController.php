<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Person\Contracts\CreditServiceInterface;
use Modules\Person\Http\Resources\Transformers\CreditTransformer;

class CreditQueryController extends Controller
{
    public function __construct(
        private readonly CreditServiceInterface $service,
        private readonly CreditTransformer $transformer,
    ) {}

    public function cast(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getCastFor($creditableType, $creditableId);

        return ApiResponse::fractalCollection($credits, $this->transformer, __('person::messages.cast'));
    }

    public function crew(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getCrewFor($creditableType, $creditableId);

        return ApiResponse::fractalCollection($credits, $this->transformer, __('person::messages.crew'));
    }

    public function forCreditable(string $creditableType, int $creditableId): JsonResponse
    {
        $credits = $this->service->getByCreditable($creditableType, $creditableId);

        return ApiResponse::paginated($credits, $this->transformer, __('person::messages.credits_index'));
    }
}
