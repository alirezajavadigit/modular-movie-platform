<?php

namespace Modules\Auth\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Services\SocialAuth\GoogleAuthService;

class GoogleRedirectController extends Controller
{
    public function __invoke(GoogleAuthService $googleAuth): JsonResponse
    {
        $redirectUrl = $googleAuth->redirect()->getTargetUrl();

        return response()->json([
            'status' => 'success',
            'data' => [
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }
}
