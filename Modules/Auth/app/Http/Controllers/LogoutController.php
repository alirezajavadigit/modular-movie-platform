<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;

class LogoutController extends Controller
{
    public function __invoke(Request $request, AuthServiceInterface $auth): JsonResponse
    {
        $auth->logout();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => __('auth-module::messages.logged_out'),
            ],
        ]);
    }
}
