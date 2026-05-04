<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;

class PaymentQueryController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    public function index(): JsonResponse
    {
        $payments = $this->service->getAllRelatedToUser((int) auth()->id());

        return ApiResponse::collection($payments, $this->transformer, __('payment::messages.index'));
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findById($id);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.show'));
    }
}
