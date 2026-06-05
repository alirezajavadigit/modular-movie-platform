<?php

namespace Modules\Authorization\DTOs;

readonly class UpdateRoleDTO
{
    public function __construct(
        public string $name,
        public ?array $permissions
    ) {}
}
