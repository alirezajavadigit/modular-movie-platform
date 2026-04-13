<?php

namespace Modules\Auth\DTOs;

readonly class ChangePasswordDTO
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
