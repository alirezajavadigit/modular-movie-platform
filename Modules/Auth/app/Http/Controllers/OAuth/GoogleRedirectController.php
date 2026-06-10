<?php

namespace Modules\Auth\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Services\SocialAuth\GoogleAuthService;
use OpenApi\Attributes as OA;

class GoogleRedirectController extends Controller
{
    #[OA\Get(
        path: '/api/v1/auth/oauth/google',
        operationId: 'auth.oauth.google.redirect',
        summary: 'Get the Google OAuth consent screen URL',
        tags: ['Auth'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthRedirect')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
