<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\UpdatePaymentRequest;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;
use Modules\Payment\Models\Payment;
use OpenApi\Attributes as OA;

class PaymentStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    #[OA\Patch(
        path: '/api/v1/payments/{payment}/verify',
        operationId: 'payment.verify',
        summary: 'Re-verify a payment against its gateway',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdatePaymentRequest'),
        parameters: [
            new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function verify(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        $this->authorize('verify', Payment::findOrFail($id));

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
