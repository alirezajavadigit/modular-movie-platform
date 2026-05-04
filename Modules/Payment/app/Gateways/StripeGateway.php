<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use RuntimeException;

final class StripeGateway implements GatewayInterface
{
    private string $secretKey;
    private string $callbackUrl;
    private string $cancelUrl;
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->secretKey   = config('payment-module.gateways.stripe.secret_key', '');
        $this->callbackUrl = config('payment-module.gateways.stripe.callback_url', '');
        $this->cancelUrl   = config('payment-module.gateways.stripe.cancel_url', '');
    }

    public function purchase(CreatePaymentDTO $dto): string
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->asForm()
            ->post("{$this->baseUrl}/checkout/sessions", [
                'mode'                 => 'payment',
                'success_url'          => $this->callbackUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => $this->cancelUrl,
                'line_items[0][price_data][currency]'                        => 'usd',
                'line_items[0][price_data][unit_amount]'                     => (int) ($dto->payable->getPayableAmount() * 100),
                'line_items[0][price_data][product_data][name]'              => $dto->payable->getPayableDescription(),
                'line_items[0][quantity]'                                    => 1,
            ]);

        $data = $response->json();

        if ($response->failed() || empty($data['url'])) {
            throw new RuntimeException('Stripe checkout session creation failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data['url'];
    }

    public function verify(string $transactionId): bool
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get("{$this->baseUrl}/checkout/sessions/{$transactionId}");

        $data = $response->json();

        return ($data['payment_status'] ?? null) === 'paid';
    }
}
