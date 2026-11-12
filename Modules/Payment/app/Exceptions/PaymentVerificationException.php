<?php

declare(strict_types=1);

namespace Modules\Payment\Exceptions;

use App\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class PaymentVerificationException extends RuntimeException
{
    private function __construct(string $message, private readonly int $statusCode)
    {
        parent::__construct($message);
    }

    public static function transactionMismatch(): self
    {
        return new self(__('payment::messages.transaction_mismatch'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function transactionMissing(): self
    {
        return new self(__('payment::messages.transaction_missing'), Response::HTTP_CONFLICT);
    }

    public static function notConfirmed(): self
    {
        return new self(__('payment::messages.not_confirmed'), Response::HTTP_CONFLICT);
    }

    public static function notSettled(): self
    {
        return new self(__('payment::messages.not_settled'), Response::HTTP_CONFLICT);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error(message: $this->getMessage(), statusCode: $this->statusCode);
    }
}
