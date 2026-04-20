<?php

namespace Modules\Authorization\DTOs;

readonly class RevokeRoleDTO
{
    public function __construct(
        public int $userId,
        public array $roleNames
    ) {}
}
