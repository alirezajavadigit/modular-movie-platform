<?php

namespace Modules\Auth\DTOs;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $password,
        public string $channel,
    ) {}
}
