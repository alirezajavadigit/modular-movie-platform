<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
use RuntimeException;

final class ZibalGateway implements GatewayInterface
{
    private string $merchant;
    private string $callbackUrl;
    private string $requestEndpoint = 'https://gateway.zibal.ir/v1/request';
    private string $verifyEndpoint  = 'https://gateway.zibal.ir/v1/verify';
    private string $startEndpoint   = 'https://gateway.zibal.ir/start/';

    public function __construct()
    {
        $this->merchant    = config('payment-module.gateways.zibal.merchant', '');
        $this->callbackUrl = config('payment-module.gateways.zibal.callback_url', '');
    }

    public function purchase(CreatePaymentDTO $paymentDTO): PurchaseResultDTO
    {
        $response = Http::post($this->requestEndpoint, [
            'merchant'    => $this->merchant,
            'amount'      => (int) ($paymentDTO->payable->getPayableAmount() * 10),
            'callbackUrl' => $this->callbackUrl,
            'description' => $paymentDTO->payable->getPayableDescription(),
        ]);

        $data = $response->json();

        if (($data['result'] ?? null) !== 100) {
            throw new RuntimeException('Zibal payment request failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return new PurchaseResultDTO($this->startEndpoint . $data['trackId'], (string) $data['trackId']);
    }

    public function verify(string $transactionId, float $amount = 0.0): bool
    {
        $response = Http::post($this->verifyEndpoint, [
            'merchant' => $this->merchant,
            'trackId'  => $transactionId,
        ]);

        $data = $response->json();

        return ($data['result'] ?? null) === 100;
    }
}
