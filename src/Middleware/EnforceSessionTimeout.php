<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Support\RouteHelper;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SecurityRequest::isApi($request)) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $allowed = config('security.session.allowed_routes', []);
        $timeout = (int) config('security.session.idle_timeout_minutes', 20);
        $lastActivity = $request->session()->get('security.last_activity');

        if ($lastActivity && $timeout > 0) {
            $expired = Carbon::parse($lastActivity)->addMinutes($timeout)->isPast();

            if ($expired && ! RouteHelper::isAllowed($request, $allowed)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return SecurityResponder::deny(
                    $request,
                    'session_idle_timeout',
                    'Your session expired due to inactivity. Please sign in again.',
                    401,
                    'login'
                );
            }
        }

        $request->session()->put('security.last_activity', now()->toDateTimeString());

        return $next($request);
    }
}
