<?php

namespace Modules\Auth\DTOs;

readonly class LoginDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
    ) {}
}
