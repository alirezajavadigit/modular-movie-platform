<?php

namespace Modules\Auth\DTOs;

readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $identifier,
    ) {}
}
