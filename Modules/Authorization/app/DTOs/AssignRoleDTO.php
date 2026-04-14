<?php

namespace Modules\Authorization\DTOs;

readonly class AssignRoleDTO
{
    public function __construct(
        public int $userId,
        public array $roleNames
    ) {}
}
