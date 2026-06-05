<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\UpdatePaymentRequest;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;

class PaymentStatusController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    public function verify(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $dto = new UpdatePaymentDTO(
            paymentId:     $id,
            status:        PaymentStatus::PENDING,
            transactionId: $validated['transaction_id'] ?? null,
        );

        $payment = $this->service->verify($dto);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.verified'));
    }
}
