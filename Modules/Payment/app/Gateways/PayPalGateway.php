<?php

namespace Modules\Payment\Gateways;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
use Modules\Payment\Exceptions\PaymentNotSettledException;
use RuntimeException;

final class PayPalGateway implements GatewayInterface
{
    private const RETRYABLE_STATUSES = [401, 403, 408, 429];

    private const NOT_YET_PAYABLE_ISSUES = ['ORDER_NOT_APPROVED', 'PAYER_ACTION_REQUIRED'];

    private const DECLINED_CAPTURE_STATUSES = ['DECLINED', 'FAILED'];

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

    public function purchase(CreatePaymentDTO $paymentDTO): PurchaseResultDTO
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value'         => number_format($paymentDTO->payable->getPayableAmount(), 2, '.', ''),
                        ],
                        'description' => $paymentDTO->payable->getPayableDescription(),
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

        return new PurchaseResultDTO($approvalLink['href'], (string) $data['id']);
    }

    public function verify(string $transactionId, float $amount = 0.0): bool
    {
        $orderUrl = "{$this->baseUrl}/v2/checkout/orders/" . rawurlencode($transactionId);

        $response = $this->send(fn (string $token): Response => Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => "capture-{$transactionId}"])
            ->post("{$orderUrl}/capture"));

        if ($response->status() === 404) {
            return false;
        }

        if ($response->status() === 422) {
            $issue = $response->json('details.0.issue');

            if ($issue === 'ORDER_ALREADY_CAPTURED') {
                return $this->captureCompleted($this->send(fn (string $token): Response => Http::withToken($token)->get($orderUrl))->json());
            }

            if (in_array($issue, self::NOT_YET_PAYABLE_ISSUES, true)) {
                throw new PaymentNotSettledException("The PayPal order is not payable yet [{$issue}].");
            }

            return false;
        }

        if ($response->failed()) {
            throw new PaymentNotSettledException("PayPal capture did not return a final result [{$response->status()}].");
        }

        return $this->captureCompleted($response->json());
    }

    private function captureCompleted(?array $order): bool
    {
        $status = $order['purchase_units'][0]['payments']['captures'][0]['status'] ?? null;

        if ($status === 'COMPLETED') {
            return true;
        }

        if (in_array($status, self::DECLINED_CAPTURE_STATUSES, true)) {
            return false;
        }

        throw new PaymentNotSettledException("The PayPal capture is not final yet [{$status}].");
    }

    private function send(Closure $request): Response
    {
        try {
            $response = $request($this->getAccessToken());
        } catch (ConnectionException|RuntimeException $e) {
            throw new PaymentNotSettledException('PayPal is unavailable.', previous: $e);
        }

        if ($response->serverError() || in_array($response->status(), self::RETRYABLE_STATUSES, true)) {
            throw new PaymentNotSettledException("PayPal did not return a final result [{$response->status()}].");
        }

        return $response;
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
