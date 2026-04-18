<?php

namespace Modules\Person\DTOs;

readonly class CreateCreditDTO
{
    public function __construct(
        public int     $personId,
        public string  $creditableType,
        public int     $creditableId,
        public string  $role,
        public ?string $characterName,
        public ?string $creditedAs,
        public ?string $department,
        public int     $order,
    ) {}
}
