<?php

namespace Pitbphp\Security\Http\Controllers\Api\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Http\Requests\Api\SecurityApiLoginRequest;
use Pitbphp\Security\Services\LoginAttemptService;
use Pitbphp\Security\Services\SanctumTokenService;
use Pitbphp\Security\Support\SanctumInstaller;
use Pitbphp\Security\Support\SecurityResponder;

class LoginController extends Controller
{
    public function login(
        SecurityApiLoginRequest $request,
        LoginAttemptService $loginAttempts,
        SanctumTokenService $tokens
    ): JsonResponse {
        if (Auth::guard(config('security.guard', 'web'))->check()) {
            return SecurityResponder::apiError('Already authenticated.', 'already_authenticated', 400);
        }

        if (! SanctumInstaller::isAvailable() || ! $tokens->isAvailable()) {
            return SecurityResponder::apiError(
                'Sanctum is not installed. Run security:install --mode=api.',
                'sanctum_missing',
                503
            );
        }

        $email = (string) $request->input('email');
        $guard = config('security.guard', 'web');
        $userModel = config('security.user.model');
        $user = (new $userModel)->newQuery()->where('email', $email)->first();

        if ($user && $loginAttempts->isLocked($user)) {
            return SecurityResponder::apiError(
                'Your account is locked. Try again later or contact support.',
                'account_locked',
                403
            );
        }

        if (! Auth::guard($guard)->attempt($request->only('email', 'password'))) {
            if ($user) {
                $loginAttempts->recordFailure($user, $email, $request->ip());
            }

            return SecurityResponder::apiError(
                'These credentials do not match our records.',
                'auth_failed',
                401
            );
        }

        $user = Auth::guard($guard)->user();
        $loginAttempts->clear($user);

        if (! SanctumInstaller::userHasApiTokens()) {
            Auth::guard($guard)->logout();

            return SecurityResponder::apiError(
                'Add Laravel\\Sanctum\\HasApiTokens to your User model.',
                'sanctum_not_configured',
                500
            );
        }

        $tokenName = (string) ($request->input('device_name')
            ?: config('security.api.auth.token_name', 'api'));

        $token = $user->createToken($tokenName);

        Auth::guard($guard)->logout();

        return SecurityResponder::apiSuccess('Authenticated.', [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
