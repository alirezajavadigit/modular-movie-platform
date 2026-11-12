<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;
use Modules\Payment\Exceptions\PaymentNotSettledException;
use RuntimeException;
use Throwable;
use ZarinPal\Sdk\ClientBuilder;
use ZarinPal\Sdk\Endpoint\PaymentGateway\PaymentGateway;
use ZarinPal\Sdk\Endpoint\PaymentGateway\RequestTypes\InquiryRequest;
use ZarinPal\Sdk\Endpoint\PaymentGateway\RequestTypes\RequestRequest;
use ZarinPal\Sdk\Endpoint\PaymentGateway\RequestTypes\VerifyRequest;
use ZarinPal\Sdk\HttpClient\Exception\ResponseException;
use ZarinPal\Sdk\Options;
use ZarinPal\Sdk\ZarinPal;

final class ZarinPalGateway implements GatewayInterface
{
    private const DECLINED_VERIFY_CODES = [-50, -53, -54];

    private const NOT_PAID_CODE = -51;

    private const FINAL_UNPAID_STATUSES = ['FAILED', 'REVERSED'];

    private PaymentGateway $paymentGateway;

    public function __construct(?ClientBuilder $clientBuilder = null)
    {
        $options = new Options([
            'merchant_id' => config('payment-module.gateways.zarinpal.merchant_id', ''),
            'sandbox' => (bool) config('payment-module.gateways.zarinpal.sandbox', false),
            'client_builder' => $clientBuilder ?? new ClientBuilder(),
        ]);

        $this->paymentGateway = (new ZarinPal($options))->paymentGateway();
    }
    private function convertToLocalIranianNumber(string $number): string
    {
        $number = trim($number);

        if (str_starts_with($number, '+98')) {
            return '0' . substr($number, 3);
        }

        // If it already starts with 98 (without +), convert it
        if (str_starts_with($number, '98')) {
            return '0' . substr($number, 2);
        }

        // Return as is if already in local format or unknown format
        return $number;
    }
    public function purchase(CreatePaymentDTO $paymentDTO): PurchaseResultDTO
    {
        $request = new RequestRequest();
        $request->amount = (int) ($paymentDTO->payable->getPayableAmount() * 10);
        $request->callback_url = $this->resolveCallbackUrl();
        $request->mobile =  '09120000000';
        $request->description = $paymentDTO->payable->getPayableDescription();

        try {
            $response = $this->paymentGateway->request($request);
        } catch (ResponseException $e) {
            throw new RuntimeException('ZarinPal payment request failed: ' . $e->getMessage(), previous: $e);
        }

        return new PurchaseResultDTO(
            $this->paymentGateway->getRedirectUrl($response->authority),
            $response->authority,
        );
    }

    public function verify(string $transactionId, float $amount = 0.0): bool
    {

        $request = new VerifyRequest();
        $request->authority = $transactionId;
        $request->amount = (int) ($amount * 10);
        $request->mobile = Auth::user()?->phone ?? '09120000000';

        try {
            $response = $this->paymentGateway->verify($request);
        } catch (InvalidArgumentException) {
            return false;
        } catch (ResponseException $e) {
            if ($e->getCode() === self::NOT_PAID_CODE) {
                return $this->declinedUnlessStillOpen($transactionId, $e);
            }

            if (in_array($e->getCode(), self::DECLINED_VERIFY_CODES, true)) {
                return false;
            }

            throw new PaymentNotSettledException('ZarinPal verification did not return a final result.', previous: $e);
        } catch (Throwable $e) {
            throw new PaymentNotSettledException('ZarinPal verification is unavailable.', previous: $e);
        }

        return in_array($response->code, [100, 101], true);
    }

    private function declinedUnlessStillOpen(string $authority, ResponseException $verifyError): bool
    {
        $request = new InquiryRequest();
        $request->authority = $authority;

        try {
            $status = $this->paymentGateway->inquiry($request)->status ?? null;
        } catch (Throwable $e) {
            throw new PaymentNotSettledException('ZarinPal inquiry is unavailable.', previous: $e);
        }

        if (in_array($status, self::FINAL_UNPAID_STATUSES, true)) {
            return false;
        }

        throw new PaymentNotSettledException("ZarinPal session is not final yet [{$status}].", previous: $verifyError);
    }

    private function resolveCallbackUrl(): string
    {
        $configured = config('payment-module.gateways.zarinpal.callback_url');

        return $configured !== null && $configured !== ''
            ? $configured
            : route('payment.callback', ['driver' => 'zarinpal']);
    }
}
