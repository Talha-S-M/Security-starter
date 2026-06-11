<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\LoginAttemptService;
use Pitbphp\Security\Services\SanctumTokenService;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    public function __construct(
        protected LoginAttemptService $loginAttempts,
        protected SanctumTokenService $tokens
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'isSecurityActive') && ! $user->isSecurityActive()) {
            return $this->denyAccess($request, 'account_disabled', 'Your account has been disabled. Contact an administrator.');
        }

        if (method_exists($user, 'hasExpiredAccess') && $user->hasExpiredAccess()) {
            return $this->denyAccess($request, 'access_expired', 'Your temporary access has expired.');
        }

        if ($this->loginAttempts->isLocked($user)) {
            return $this->denyAccess($request, 'account_locked', 'Your account is locked. Try again later or contact support.');
        }

        return $next($request);
    }

    protected function denyAccess(Request $request, string $code, string $message): Response
    {
        if (SecurityRequest::isApi($request)) {
            $this->tokens->revokeCurrent($request);
        } else {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return SecurityResponder::deny($request, $code, $message, 403, 'login');
    }
}
