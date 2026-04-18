<?php

namespace Modules\Auth\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;
use Modules\Auth\Services\SocialAuth\GoogleAuthService;

class GoogleCallbackController extends Controller
{
    public function __invoke(AuthServiceInterface $auth, GoogleAuthService $googleAuth): JsonResponse
    {
        $socialUserDto = $googleAuth->resolveUser();

        $result = $auth->handleGoogleCallback($socialUserDto);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $result['token'],
                'user' => fractal($result['user'], new UserTransformer())->toArray()['data'],
            ],
        ]);
    }
}
