<?php

namespace Pitbphp\Security\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityRequest
{
    public static function mode(): string
    {
        return config('security.mode', 'web');
    }

    public static function isApiEnabled(): bool
    {
        return in_array(self::mode(), ['api', 'hybrid'], true);
    }

    public static function isApi(Request $request): bool
    {
        if (self::mode() === 'api') {
            return true;
        }

        if (self::mode() === 'web') {
            return false;
        }

        if ($request->bearerToken() !== null) {
            return true;
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return true;
        }

        $prefix = trim(config('security.api.path_prefix', 'api/security'), '/');

        if ($prefix !== '' && $request->is($prefix, $prefix.'/*')) {
            return true;
        }

        $routePrefix = config('security.api.route_name_prefix', 'security.api.');

        if ($routePrefix !== '' && $request->routeIs(rtrim($routePrefix, '.').'.*')) {
            return true;
        }

        $guard = config('security.api.guard', 'sanctum');

        return Auth::guard($guard)->check() && ! $request->hasSession();
    }

    public static function guard(): string
    {
        return self::isApiEnabled() && self::mode() === 'api'
            ? config('security.api.guard', 'sanctum')
            : config('security.guard', config('auth.defaults.guard', 'web'));
    }

    public static function currentTokenId(Request $request): ?string
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();

        return $token ? (string) $token->id : null;
    }
}
