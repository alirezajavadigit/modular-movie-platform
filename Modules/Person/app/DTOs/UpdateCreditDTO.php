<?php

namespace Modules\Person\DTOs;

readonly class UpdateCreditDTO
{
    public function __construct(
        public ?string $role,
        public ?string $characterName,
        public ?string $creditedAs,
        public ?string $department,
        public ?int    $order,
    ) {}
}
