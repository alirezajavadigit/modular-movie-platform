<?php

namespace Modules\Payment\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
use Modules\Payment\Exceptions\PaymentNotSettledException;
use RuntimeException;

final class ZibalGateway implements GatewayInterface
{
    private const VERIFIED_RESULTS = [100, 201];

    private const DECLINED_RESULTS = [203];

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
        try {
            $response = Http::post($this->verifyEndpoint, [
                'merchant' => $this->merchant,
                'trackId'  => $transactionId,
            ]);
        } catch (ConnectionException $e) {
            throw new PaymentNotSettledException('Zibal is unreachable.', previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentNotSettledException("Zibal verify did not return a final result [{$response->status()}].");
        }

        $result = $response->json('result');

        return match (true) {
            in_array($result, self::VERIFIED_RESULTS, true) => true,
            in_array($result, self::DECLINED_RESULTS, true) => false,
            default => throw new PaymentNotSettledException("Zibal verify returned a non-final result [{$result}]."),
        };
    }
}
