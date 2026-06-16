<?php

namespace Pitbphp\Security\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityResponder
{
    public static function apiSuccess(
        ?string $message = null,
        mixed $content = null,
        ?string $description = null,
        int $status = 200
    ): JsonResponse {
        return response()->json(self::apiPayload(true, $status, $message, $description, $content), $status);
    }

    public static function apiError(
        string $message,
        ?string $description = null,
        int $status = 400,
        mixed $content = null
    ): JsonResponse {
        return response()->json(self::apiPayload(false, $status, $message, $description, $content), $status);
    }

    public static function deny(
        Request $request,
        string $errorCode,
        string $message,
        int $status = 403,
        ?string $redirectRoute = null,
        array $extra = []
    ): Response {
        if (SecurityRequest::isApi($request) || $request->expectsJson()) {
            return self::apiError(
                $message,
                $extra === [] ? $errorCode : trim($errorCode.' | '.json_encode($extra)),
                $status,
                null
            );
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

            return self::apiError(
                'Password expired.',
                'password_expired',
                403,
                ['action' => $apiAction]
            );
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
            return self::apiError(
                $payload['message'],
                $payload['error_code'],
                403,
                ['action' => $payload['action']]
            );
        }

        return redirect()->route(SecurityRoutes::name('mfa.verify'));
    }

    protected static function apiPayload(
        bool $success,
        int $status,
        ?string $message,
        ?string $description,
        mixed $content
    ): array {
        if (! config('security.api.response.use_envelope', true)) {
            return array_filter([
                'message' => $message,
                'description' => $description,
                'content' => $content,
            ], static fn ($value) => $value !== null);
        }

        $keys = config('security.api.response.keys', []);

        return [
            $keys['code'] ?? 'Code' => $status,
            $keys['success'] ?? 'Success' => $success,
            $keys['message'] ?? 'Message' => $message,
            $keys['description'] ?? 'Description' => $description,
            $keys['content'] ?? 'Content' => $content,
        ];
    }
}
