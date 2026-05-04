<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use RuntimeException;

final class PayPalGateway implements GatewayInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $callbackUrl;
    private string $cancelUrl;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId     = config('payment-module.gateways.paypal.client_id', '');
        $this->clientSecret = config('payment-module.gateways.paypal.client_secret', '');
        $this->callbackUrl  = config('payment-module.gateways.paypal.callback_url', '');
        $this->cancelUrl    = config('payment-module.gateways.paypal.cancel_url', '');
        $this->baseUrl      = config('payment-module.gateways.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function purchase(CreatePaymentDTO $dto): string
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value'         => number_format($dto->payable->getPayableAmount(), 2, '.', ''),
                        ],
                        'description' => $dto->payable->getPayableDescription(),
                    ],
                ],
                'application_context' => [
                    'return_url' => $this->callbackUrl,
                    'cancel_url' => $this->cancelUrl,
                ],
            ]);

        $data = $response->json();

        if ($response->failed() || ($data['status'] ?? null) !== 'CREATED') {
            throw new RuntimeException('PayPal order creation failed.');
        }

        $approvalLink = collect($data['links'])->firstWhere('rel', 'approve');

        if (!$approvalLink) {
            throw new RuntimeException('PayPal approval link not found in response.');
        }

        return $approvalLink['href'];
    }

    public function verify(string $transactionId): bool
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$transactionId}/capture");

        $data = $response->json();

        return ($data['status'] ?? null) === 'COMPLETED';
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new RuntimeException('Failed to obtain PayPal access token.');
        }

        return $data['access_token'];
    }
}
