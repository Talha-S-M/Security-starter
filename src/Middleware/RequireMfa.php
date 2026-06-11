<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Support\RouteHelper;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function __construct(
        protected MfaService $mfa
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.mfa.enabled')) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $allowed = SecurityRequest::isApi($request)
            ? config('security.api.allowed_route_names', [])
            : config('security.mfa.allowed_routes', []);

        if (RouteHelper::isAllowed($request, $allowed)) {
            return $next($request);
        }

        if ($this->mfa->isVerified($user, $request)) {
            return $next($request);
        }

        $tokenId = SecurityRequest::currentTokenId($request);

        if (SecurityRequest::isApi($request)) {
            if (! Cache::has($this->issuedKey($user, $tokenId))) {
                $this->mfa->issue($user, $tokenId);
                Cache::put($this->issuedKey($user, $tokenId), true, now()->addMinutes(5));
            }

            return SecurityResponder::mfaRequired($request);
        }

        if (! $request->session()->has('security.mfa_issued')) {
            $this->mfa->issue($user);
            $request->session()->put('security.mfa_issued', true);
        }

        return SecurityResponder::mfaRequired($request);
    }

    protected function issuedKey($user, ?string $tokenId): string
    {
        return 'pitb_security_mfa_issued_'.$user->getAuthIdentifier().'_'.($tokenId ?? 'session');
    }
}
