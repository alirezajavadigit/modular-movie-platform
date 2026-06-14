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
use OpenApi\Attributes as OA;

class PaymentQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/payments',
        operationId: 'payment.index',
        summary: 'List the payments of the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentCollection')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->service->getAllRelatedToUser((int) auth()->id());

        return ApiResponse::fractal($payments, $this->transformer, __('payment::messages.index'));
    }

    #[OA\Get(
        path: '/api/v1/payments/{payment}',
        operationId: 'payment.show',
        summary: 'Show a payment of the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findById($id);
        $this->authorize('view', $payment);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.show'));
    }
}
