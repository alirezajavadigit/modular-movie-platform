<?php

namespace Modules\Payment\Gateways;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
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

    public function purchase(CreatePaymentDTO $paymentDTO): PurchaseResultDTO
    {
        $response = $this->client()
            ->asForm()
            ->post('/checkout/sessions', [
                'mode'        => 'payment',
                'success_url' => $this->callbackUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $this->cancelUrl,
                'line_items[0][price_data][currency]'           => 'usd',
                'line_items[0][price_data][unit_amount]'        => (int) ($paymentDTO->payable->getPayableAmount() * 100),
                'line_items[0][price_data][product_data][name]' => $paymentDTO->payable->getPayableDescription(),
                'line_items[0][quantity]'                       => 1,
            ]);

        $data = $response->json();

        if ($response->failed() || empty($data['url'])) {
            throw new RuntimeException('Stripe checkout session creation failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        return new PurchaseResultDTO($data['url'], (string) $data['id']);
    }

    public function verify(string $transactionId, float $amount = 0.0): bool
    {
        return $this->client()
            ->get("/checkout/sessions/{$transactionId}")
            ->json('payment_status') === 'paid';
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->withBasicAuth($this->secretKey, '');
    }
}
