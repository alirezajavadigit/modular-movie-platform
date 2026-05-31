<?php

namespace Modules\User\DTOs;

readonly class CreateUserDTO
{
    public function __construct(
        public string  $name,
        public ?string $email,
        public ?string $phone,
        public string  $password,
        public array   $roles = [],
    ) {}
}
