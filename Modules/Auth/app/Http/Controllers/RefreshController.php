<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;

class RefreshController extends Controller
{
    public function __invoke(Request $request, AuthServiceInterface $auth): JsonResponse
    {
        $token = $auth->refresh();

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
