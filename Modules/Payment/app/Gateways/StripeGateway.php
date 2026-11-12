<?php

namespace Modules\Payment\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
use Modules\Payment\Exceptions\PaymentNotSettledException;
use Modules\Payment\Support\MinorUnits;
use RuntimeException;

final class StripeGateway implements GatewayInterface
{
    private const CURRENCY = 'usd';

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
                'line_items[0][price_data][currency]'           => self::CURRENCY,
                'line_items[0][price_data][unit_amount]'        => MinorUnits::fromDecimal($paymentDTO->payable->getPayableAmount()),
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
        try {
            $response = $this->client()->get('/checkout/sessions/' . rawurlencode($transactionId));
        } catch (ConnectionException $e) {
            throw new PaymentNotSettledException('Stripe is unreachable.', previous: $e);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new PaymentNotSettledException('Stripe did not return a final result.');
        }

        if ($response->failed()) {
            return false;
        }

        $session = $response->json();

        if (($session['id'] ?? null) !== $transactionId) {
            return false;
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            if (($session['status'] ?? null) === 'open') {
                throw new PaymentNotSettledException('The Stripe checkout session has not been paid yet.');
            }

            return false;
        }

        return strtolower((string) ($session['currency'] ?? '')) === self::CURRENCY
            && ($session['amount_total'] ?? null) === MinorUnits::fromDecimal($amount);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->withBasicAuth($this->secretKey, '');
    }
}
