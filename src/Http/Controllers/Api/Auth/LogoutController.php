<?php

namespace Pitbphp\Security\Http\Controllers\Api\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pitbphp\Security\Services\SanctumTokenService;
use Pitbphp\Security\Support\SecurityResponder;

class LogoutController extends Controller
{
    public function logout(Request $request, SanctumTokenService $tokens): JsonResponse
    {
        $tokens->revokeCurrent($request);

        return SecurityResponder::apiSuccess('Logged out.');
    }
}
