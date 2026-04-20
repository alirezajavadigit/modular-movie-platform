<?php

namespace Modules\Authorization\DTOs;

readonly class SyncRoleDTO
{
    public function __construct(
        public int $userId,
        public array $roleNames
    ) {}
}
