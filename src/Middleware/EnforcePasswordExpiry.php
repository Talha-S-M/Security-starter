<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Support\RouteHelper;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordExpiry
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $allowed = SecurityRequest::isApi($request)
            ? config('security.api.allowed_route_names', [])
            : config('security.password.allowed_routes', []);

        if (RouteHelper::isAllowed($request, $allowed)) {
            return $next($request);
        }

        if (method_exists($user, 'isPasswordExpired') && $user->isPasswordExpired()) {
            return SecurityResponder::passwordExpired($request);
        }

        return $next($request);
    }
}
