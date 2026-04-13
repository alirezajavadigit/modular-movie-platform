<?php

namespace Modules\Auth\DTOs;

use Carbon\Carbon;

readonly class SocialUserDTO
{
    public function __construct(
        public string $provider,
        public string $providerId,
        public string $email,
        public ?string $name,
        public ?string $avatar,
        public ?string $token,
        public ?string $refreshToken,
        public ?Carbon $tokenExpiresAt,
    ) {}
}
