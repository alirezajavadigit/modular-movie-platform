<?php

namespace Modules\Authorization\DTOs;

readonly class AssignPermissionDTO
{
    public function __construct(
        public int $userId,
        public array $permissionNames
    ) {}
}
