<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;
use Modules\Payment\Models\Payment;

class PaymentQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->service->getAllRelatedToUser((int) auth()->id());

        return ApiResponse::fractal($payments, $this->transformer, __('payment::messages.index'));
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findById($id);
        $this->authorize('view', $payment);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.show'));
    }
}
