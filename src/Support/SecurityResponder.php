<?php

namespace Pitbphp\Security\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityResponder
{
    public static function deny(
        Request $request,
        string $errorCode,
        string $message,
        int $status = 403,
        ?string $redirectRoute = null,
        array $extra = []
    ): Response {
        if (SecurityRequest::isApi($request) || $request->expectsJson()) {
            return response()->json(array_merge([
                'message' => $message,
                'error_code' => $errorCode,
            ], $extra), $status);
        }

        if ($redirectRoute) {
            if (\Illuminate\Support\Facades\Route::has($redirectRoute)) {
                return redirect()->route($redirectRoute)->withErrors(['email' => $message]);
            }

            return redirect('/login')->withErrors(['email' => $message]);
        }

        if (\Illuminate\Support\Facades\Route::has('login')) {
            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return redirect('/login')->withErrors(['email' => $message]);
    }

    public static function passwordExpired(Request $request): Response
    {
        if (SecurityRequest::isApi($request) || $request->expectsJson()) {
            $apiAction = \Illuminate\Support\Facades\Route::has(SecurityRoutes::apiName('password.update'))
                ? route(SecurityRoutes::apiName('password.update'))
                : SecurityRoutes::apiPath('password/update');

            return response()->json([
                'message' => 'Password expired.',
                'error_code' => 'password_expired',
                'action' => $apiAction,
            ], 403);
        }

        return redirect()->route(SecurityRoutes::name('password.expired'));
    }

    public static function mfaRequired(Request $request): JsonResponse|RedirectResponse
    {
        $payload = [
            'message' => 'MFA verification required.',
            'error_code' => 'mfa_required',
            'action' => SecurityRequest::isApi($request)
                ? (\Illuminate\Support\Facades\Route::has(SecurityRoutes::apiName('mfa.verify'))
                    ? route(SecurityRoutes::apiName('mfa.verify'))
                    : SecurityRoutes::apiPath('mfa/verify'))
                : (\Illuminate\Support\Facades\Route::has(SecurityRoutes::name('mfa.verify'))
                    ? route(SecurityRoutes::name('mfa.verify'))
                    : SecurityRoutes::path('mfa/verify')),
        ];

        if (SecurityRequest::isApi($request) || $request->expectsJson()) {
            return response()->json($payload, 403);
        }

        return redirect()->route(SecurityRoutes::name('mfa.verify'));
    }
}
