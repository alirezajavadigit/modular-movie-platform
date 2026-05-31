<?php

namespace Modules\User\DTOs;

readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $password = null,
        public ?array  $roles = null,
    ) {}
}
