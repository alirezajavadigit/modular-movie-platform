<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;
use OpenApi\Attributes as OA;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/payments/callback/{driver}',
        operationId: 'payment.callback',
        summary: 'Gateway return URL that settles a payment',
        description: 'Called by the payment gateway after checkout. Gateway-specific query parameters (authority, status, token, etc.) are forwarded verbatim to the driver. When a frontend redirect URL is configured the client is redirected there with payment and status query parameters; otherwise the settled payment is returned as JSON.',
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(name: 'driver', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'stripe'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentItem')]
    #[OA\Response(response: 302, description: 'Redirect to the configured frontend with payment and status query parameters')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function handle(Request $request, string $driver): RedirectResponse|JsonResponse
    {

        $payment = $this->service->callback($driver, $request->query());

        $redirectUrl = config('payment-module.frontend_redirect_url');

        if ($redirectUrl) {
            $separator = str_contains($redirectUrl, '?') ? '&' : '?';
            $query     = http_build_query(['payment' => $payment->id, 'status' => $payment->status->value]);

            return redirect()->away($redirectUrl . $separator . $query);
        }

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.verified'));
    }
}
