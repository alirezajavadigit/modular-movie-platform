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
    private const TEMPORARY_STATUSES = [408, 409, 429];

    private const CREDENTIAL_STATUSES = [401, 403];

    private const PAYER_ACTION_ISSUES = [
        'ORDER_NOT_APPROVED',
        'PAYER_ACTION_REQUIRED',
        'INSTRUMENT_DECLINED',
        'TRANSACTION_REFUSED',
        'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED',
        'CARD_EXPIRED',
    ];

    private const ALREADY_CAPTURED_ISSUE = 'ORDER_ALREADY_CAPTURED';

    private const DECLINED_CAPTURE_STATUSES = ['DECLINED', 'FAILED'];

    private const VOIDED_ORDER_STATUS = 'VOIDED';

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

        $capture = $this->send(fn (string $token): Response => Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => "capture-{$transactionId}"])
            ->post("{$orderUrl}/capture"));

        if ($capture->successful()) {
            return $this->captureOutcome($capture->json());
        }

        if ($capture->status() !== 422) {
            throw PaymentNotSettledException::needsReview('PayPal rejected the capture request.', $this->errorContext($capture));
        }

        $issues = $this->issues($capture);
        $order = $this->send(fn (string $token): Response => Http::withToken($token)->get($orderUrl));

        if ($order->failed()) {
            throw PaymentNotSettledException::needsReview('PayPal order details could not be read.', $this->errorContext($order) + ['issues' => $issues]);
        }

        $orderStatus = $this->code($order->json('status'));

        if (in_array(self::ALREADY_CAPTURED_ISSUE, $issues, true) || $orderStatus === 'COMPLETED') {
            return $this->captureOutcome($order->json());
        }

        if ($orderStatus === self::VOIDED_ORDER_STATUS) {
            return false;
        }

        $context = $this->errorContext($capture) + ['issues' => $issues, 'order_status' => $orderStatus];

        if (array_intersect($issues, self::PAYER_ACTION_ISSUES) !== []) {
            throw PaymentNotSettledException::payerActionRequired('The PayPal order needs action from the payer.', $context);
        }

        throw PaymentNotSettledException::needsReview('PayPal returned an unrecognized business error.', $context);
    }

    private function captureOutcome(?array $order): bool
    {
        $status = $this->code($order['purchase_units'][0]['payments']['captures'][0]['status'] ?? null);

        if ($status === 'COMPLETED') {
            return true;
        }

        if (in_array($status, self::DECLINED_CAPTURE_STATUSES, true)) {
            return false;
        }

        if ($status === null && $this->code($order['status'] ?? null) === self::VOIDED_ORDER_STATUS) {
            return false;
        }

        throw PaymentNotSettledException::temporary('The PayPal capture is not final yet.', ['capture_status' => $status]);
    }

    private function send(Closure $request): Response
    {
        try {
            $response = $request($this->getAccessToken());
        } catch (PaymentNotSettledException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            throw PaymentNotSettledException::temporary('PayPal is unreachable.', previous: $e);
        } catch (RuntimeException $e) {
            throw PaymentNotSettledException::needsReview('PayPal authentication failed.', previous: $e);
        }

        if ($response->serverError() || in_array($response->status(), self::TEMPORARY_STATUSES, true)) {
            throw PaymentNotSettledException::temporary('PayPal did not return a final result.', $this->errorContext($response));
        }

        if (in_array($response->status(), self::CREDENTIAL_STATUSES, true)) {
            throw PaymentNotSettledException::needsReview('PayPal rejected the API credentials or permissions.', $this->errorContext($response));
        }

        return $response;
    }

    private function issues(Response $response): array
    {
        $details = $response->json('details');

        if (!is_array($details)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($detail) => $this->code(is_array($detail) ? ($detail['issue'] ?? null) : null), $details)));
    }

    private function errorContext(Response $response): array
    {
        $debugId = $response->json('debug_id');

        return [
            'http_status' => $response->status(),
            'debug_id'    => is_string($debugId) ? substr((string) preg_replace('/[^A-Za-z0-9]/', '', $debugId), 0, 32) : null,
        ];
    }

    private function code(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $code = substr((string) preg_replace('/[^A-Z0-9_]/', '', strtoupper($value)), 0, 64);

        return $code === '' ? null : $code;
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->serverError() || in_array($response->status(), self::TEMPORARY_STATUSES, true)) {
            throw PaymentNotSettledException::temporary('PayPal authentication is temporarily unavailable.', ['http_status' => $response->status()]);
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new RuntimeException('Failed to obtain PayPal access token.');
        }

        return $data['access_token'];
    }
}
