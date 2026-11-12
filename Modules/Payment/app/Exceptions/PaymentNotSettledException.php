<?php

declare(strict_types=1);

namespace Modules\Payment\Exceptions;

use RuntimeException;
use Throwable;

final class PaymentNotSettledException extends RuntimeException
{
    public const TEMPORARY = 'temporary';

    public const PAYER_ACTION = 'payer_action';

    public const REVIEW = 'review';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly string $reason = self::TEMPORARY,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function temporary(string $message, array $context = [], ?Throwable $previous = null): self
    {
        return new self($message, previous: $previous, reason: self::TEMPORARY, context: $context);
    }

    public static function payerActionRequired(string $message, array $context = []): self
    {
        return new self($message, reason: self::PAYER_ACTION, context: $context);
    }

    public static function needsReview(string $message, array $context = [], ?Throwable $previous = null): self
    {
        return new self($message, previous: $previous, reason: self::REVIEW, context: $context);
    }

    public function requiresPayerAction(): bool
    {
        return $this->reason === self::PAYER_ACTION;
    }

    public function requiresReview(): bool
    {
        return $this->reason === self::REVIEW;
    }
}
