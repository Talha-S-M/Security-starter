<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Support\RouteHelper;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Pitbphp\Security\Support\SecurityRoutes;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.mfa.enabled')) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user || ! method_exists($user, 'needsMfaSetup') || ! $user->needsMfaSetup()) {
            return $next($request);
        }

        if (method_exists($user, 'isPasswordExpired') && $user->isPasswordExpired()) {
            return $next($request);
        }

        $allowed = SecurityRequest::isApi($request)
            ? config('security.api.allowed_route_names', [])
            : config('security.mfa.setup_allowed_routes', []);

        if (RouteHelper::isAllowed($request, $allowed)) {
            return $next($request);
        }

        return SecurityRequest::isApi($request)
            ? SecurityResponder::mfaRequired($request)
            : redirect()->route(SecurityRoutes::name('mfa.setup'));
    }
}
