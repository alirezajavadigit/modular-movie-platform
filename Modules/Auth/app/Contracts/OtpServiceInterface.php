<?php

namespace Modules\Auth\Contracts;

use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Models\User;

interface OtpServiceInterface
{
    public function generate(User $user, string $channel): OtpDTO;

    public function verify(User $user, string $code): bool;

    public function dispatch(User $user, string $channel): void;
}
