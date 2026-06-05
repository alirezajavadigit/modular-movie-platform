<?php

namespace Modules\Authorization\DTOs;

readonly class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public string $guardName,
        public ?array $permissions
    ) {}
}
