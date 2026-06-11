<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pitbphp\Security\Services\SanctumTokenService;
use Pitbphp\Security\Support\RouteHelper;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Symfony\Component\HttpFoundation\Response;

class EnforceTokenTimeout
{
    public function __construct(
        protected SanctumTokenService $tokens
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! SecurityRequest::isApi($request)) {
            return $next($request);
        }

        $timeout = (int) config('security.api.token_idle_timeout_minutes', 0);

        if ($timeout <= 0) {
            $this->tokens->touch($request);

            return $next($request);
        }

        $allowed = config('security.api.allowed_route_names', []);

        if ($this->tokens->isIdleExpired($request) && ! RouteHelper::isAllowed($request, $allowed)) {
            $this->tokens->revokeCurrent($request);

            return SecurityResponder::deny(
                $request,
                'token_idle_timeout',
                'API token expired due to inactivity.',
                401
            );
        }

        $this->tokens->touch($request);

        return $next($request);
    }
}
