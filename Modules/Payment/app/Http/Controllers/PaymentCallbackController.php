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

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

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
