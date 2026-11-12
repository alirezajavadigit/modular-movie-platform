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

    private const INVALID_TRACK_RESULT = 203;

    private const MERCHANT_RESULTS = [102, 103, 104];

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
            throw PaymentNotSettledException::temporary('Zibal is unreachable.', previous: $e);
        }

        if ($response->failed()) {
            throw PaymentNotSettledException::temporary('Zibal verify did not return a final result.', ['http_status' => $response->status()]);
        }

        $result = $response->json('result');
        $context = ['gateway_result' => is_int($result) ? $result : null];

        return match (true) {
            in_array($result, self::VERIFIED_RESULTS, true) => true,
            $result === self::INVALID_TRACK_RESULT           => throw PaymentNotSettledException::needsReview('Zibal reported the stored trackId as invalid.', $context),
            in_array($result, self::MERCHANT_RESULTS, true)  => throw PaymentNotSettledException::needsReview('Zibal rejected the merchant configuration.', $context),
            default                                          => throw PaymentNotSettledException::temporary('Zibal verify returned a non-final result.', $context),
        };
    }
}
