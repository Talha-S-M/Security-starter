<?php

namespace Pitbphp\Security\Support;

/**
 * Human-readable route catalog for docs and security:routes output.
 */
class SecurityRouteCatalog
{
    /**
     * @return array<string, array{group: string, method: string, path: string, name: string, description: string, auth?: bool}>
     */
    public static function entries(): array
    {
        $entries = [];

        if (SecurityRequest::isApiEnabled()) {
            $entries = array_merge($entries, self::apiAuthEntries(), self::apiEnforcementEntries());

            $entries['sanctum.csrf-cookie'] = [
                'group' => 'Sanctum (SPA)',
                'method' => 'GET',
                'path' => '/sanctum/csrf-cookie',
                'name' => 'sanctum.csrf-cookie',
                'description' => 'Sanctum default — obtain CSRF cookie before cookie-based SPA auth (hybrid).',
                'auth' => false,
            ];
        }

        if (SecurityRequest::isWebEnabled()) {
            $entries = array_merge($entries, self::webAuthEntries(), self::webSecurityEntries());
        }

        return $entries;
    }

    /**
     * @return array<string, array{group: string, method: string, path: string, name: string, description: string, auth?: bool}>
     */
    protected static function apiAuthEntries(): array
    {
        $prefix = SecurityRoutes::apiAuthPath();
        $name = fn (string $route) => SecurityRoutes::apiName($route);

        $entries = [
            $name('login') => [
                'group' => 'API auth',
                'method' => 'POST',
                'path' => '/'.$prefix.'/login',
                'name' => $name('login'),
                'description' => 'Email/password login — returns Sanctum Bearer token.',
                'auth' => false,
            ],
            $name('logout') => [
                'group' => 'API auth',
                'method' => 'POST',
                'path' => '/'.$prefix.'/logout',
                'name' => $name('logout'),
                'description' => 'Revoke the current access token.',
                'auth' => true,
            ],
        ];

        if (SecurityTier::registrationEnabled()) {
            $entries[$name('register')] = [
                'group' => 'API auth',
                'method' => 'POST',
                'path' => '/'.$prefix.'/register',
                'name' => $name('register'),
                'description' => 'Self-registration (tier-dependent: approval queue, OTP, or immediate token).',
                'auth' => false,
            ];

            if (SecurityTier::registrationUsesOtp()) {
                $entries[$name('register.verify')] = [
                    'group' => 'API auth',
                    'method' => 'POST',
                    'path' => '/'.$prefix.'/register/verify',
                    'name' => $name('register.verify'),
                    'description' => 'Verify registration email OTP and receive token (minimal tier).',
                    'auth' => false,
                ];
                $entries[$name('register.resend')] = [
                    'group' => 'API auth',
                    'method' => 'POST',
                    'path' => '/'.$prefix.'/register/resend',
                    'name' => $name('register.resend'),
                    'description' => 'Resend registration OTP.',
                    'auth' => false,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return array<string, array{group: string, method: string, path: string, name: string, description: string, auth?: bool}>
     */
    protected static function apiEnforcementEntries(): array
    {
        $prefix = SecurityRoutes::apiPath();
        $name = fn (string $route) => SecurityRoutes::apiName($route);

        return [
            $name('password.status') => [
                'group' => 'API security',
                'method' => 'GET',
                'path' => '/'.$prefix.'/password/status',
                'name' => $name('password.status'),
                'description' => 'Check password_expired / must_change_password for current token.',
                'auth' => true,
            ],
            $name('password.update') => [
                'group' => 'API security',
                'method' => 'POST',
                'path' => '/'.$prefix.'/password/update',
                'name' => $name('password.update'),
                'description' => 'Update password when expired or must_change_password is set.',
                'auth' => true,
            ],
            $name('mfa.status') => [
                'group' => 'API security',
                'method' => 'GET',
                'path' => '/'.$prefix.'/mfa/status',
                'name' => $name('mfa.status'),
                'description' => 'MFA required / verified state for current token.',
                'auth' => true,
            ],
            $name('mfa.verify') => [
                'group' => 'API security',
                'method' => 'POST',
                'path' => '/'.$prefix.'/mfa/verify',
                'name' => $name('mfa.verify'),
                'description' => 'Submit MFA OTP after login (403 mfa_required).',
                'auth' => true,
            ],
            $name('mfa.resend') => [
                'group' => 'API security',
                'method' => 'POST',
                'path' => '/'.$prefix.'/mfa/resend',
                'name' => $name('mfa.resend'),
                'description' => 'Resend MFA OTP.',
                'auth' => true,
            ],
        ];
    }

    /**
     * @return array<string, array{group: string, method: string, path: string, name: string, description: string, auth?: bool}>
     */
    protected static function webAuthEntries(): array
    {
        $entries = [
            'login' => [
                'group' => 'Web auth',
                'method' => 'GET|POST',
                'path' => '/login',
                'name' => 'login',
                'description' => 'Login form and session authentication.',
                'auth' => false,
            ],
            'logout' => [
                'group' => 'Web auth',
                'method' => 'POST',
                'path' => '/logout',
                'name' => 'logout',
                'description' => 'End web session.',
                'auth' => true,
            ],
        ];

        if (SecurityTier::registrationEnabled()) {
            $entries['register'] = [
                'group' => 'Web auth',
                'method' => 'GET|POST',
                'path' => '/register',
                'name' => 'register',
                'description' => 'Self-registration (when tier allows).',
                'auth' => false,
            ];
        }

        return $entries;
    }

    /**
     * @return array<string, array{group: string, method: string, path: string, name: string, description: string, auth?: bool}>
     */
    protected static function webSecurityEntries(): array
    {
        $prefix = SecurityRoutes::path();

        return [
            SecurityRoutes::name('home') => [
                'group' => 'Web security',
                'method' => 'GET',
                'path' => '/'.$prefix,
                'name' => SecurityRoutes::name('home'),
                'description' => 'Security package home / dashboard.',
                'auth' => true,
            ],
            SecurityRoutes::name('mfa.verify') => [
                'group' => 'Web security',
                'method' => 'GET|POST',
                'path' => '/'.$prefix.'/mfa/verify',
                'name' => SecurityRoutes::name('mfa.verify'),
                'description' => 'MFA OTP verification page.',
                'auth' => true,
            ],
            SecurityRoutes::name('mfa.setup') => [
                'group' => 'Web security',
                'method' => 'GET|POST',
                'path' => '/'.$prefix.'/mfa/setup',
                'name' => SecurityRoutes::name('mfa.setup'),
                'description' => 'Initial MFA contact setup.',
                'auth' => true,
            ],
            SecurityRoutes::name('password.expired') => [
                'group' => 'Web security',
                'method' => 'GET',
                'path' => '/'.$prefix.'/password/expired',
                'name' => SecurityRoutes::name('password.expired'),
                'description' => 'Forced password change when expired.',
                'auth' => true,
            ],
        ];
    }
}
