<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use RuntimeException;

final class ZarinPalGateway implements GatewayInterface
{
    private string $merchantId;
    private string $callbackUrl;
    private string $requestEndpoint = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private string $verifyEndpoint  = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    private string $startEndpoint   = 'https://www.zarinpal.com/pg/StartPay/';

    public function __construct()
    {
        $this->merchantId  = config('payment-module.gateways.zarinpal.merchant_id', '');
        $this->callbackUrl = config('payment-module.gateways.zarinpal.callback_url', '');
    }

    public function purchase(CreatePaymentDTO $dto): string
    {
        $response = Http::post($this->requestEndpoint, [
            'merchant_id'  => $this->merchantId,
            'amount'       => (int) ($dto->payable->getPayableAmount() * 10),
            'callback_url' => $this->callbackUrl,
            'description'  => $dto->payable->getPayableDescription(),
        ]);

        $data = $response->json();

        if (($data['data']['code'] ?? null) !== 100) {
            throw new RuntimeException('ZarinPal payment request failed: ' . ($data['errors']['message'] ?? 'Unknown error'));
        }

        return $this->startEndpoint . $data['data']['authority'];
    }

    public function verify(string $transactionId): bool
    {
        $response = Http::post($this->verifyEndpoint, [
            'merchant_id' => $this->merchantId,
            'authority'   => $transactionId,
            'amount'      => 0,
        ]);

        $data = $response->json();

        return in_array($data['data']['code'] ?? null, [100, 101], true);
    }
}
