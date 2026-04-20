<?php

namespace Modules\Auth\Services\SocialAuth;

use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\DTOs\SocialUserDTO;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function resolveUser(): SocialUserDTO
    {
        $socialUser = Socialite::driver('google')->stateless()->user();

        return new SocialUserDTO(
            provider: 'google',
            providerId: $socialUser->getId(),
            email: $socialUser->getEmail(),
            name: $socialUser->getName(),
            avatar: $socialUser->getAvatar(),
            token: $socialUser->token,
            refreshToken: $socialUser->refreshToken,
            tokenExpiresAt: $socialUser->expiresIn
                ? Carbon::now()->addSeconds($socialUser->expiresIn)
                : null,
        );
    }
}
