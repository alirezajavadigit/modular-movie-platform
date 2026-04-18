<?php

namespace Modules\Auth\DTOs;

readonly class OtpDTO
{
    public function __construct(
        public int $userId,
        public string $code,
        public string $channel,
    ) {}
}
