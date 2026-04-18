<?php

namespace Modules\Person\DTOs;

readonly class UpdatePersonDTO
{
    public function __construct(
        public ?array  $firstName,
        public ?array  $lastName,
        public ?string $slug,
        public ?array  $biography,
        public ?string $imagePath,
        public ?string $dateOfBirth,
        public ?string $dateOfDeath,
        public ?array  $placeOfBirth,
        public ?string $gender,
        public ?string $knownForDepartment,
        public ?float  $popularity,
        public ?bool   $isActive,
    ) {}
}
