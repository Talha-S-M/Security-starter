<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Mode
    |--------------------------------------------------------------------------
    |
    | web     — session-based browser apps only (default)
    | api     — Sanctum / token-based API only
    | hybrid  — both web and API; context is detected per request
    |
    */

    'mode' => env('SECURITY_MODE', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Security tier (strict / moderate / minimal)
    |--------------------------------------------------------------------------
    |
    | strict   — no public registration; only super-admin/admin create users.
    |             Admin-provisioned changes require super-admin approval.
    | moderate — public registration on; new accounts need admin/super-admin
    |             approval before sign-in. Admin changes still use approval queue.
    | minimal      — public registration on with email OTP; account is active
    |             immediately with default (minimal) role; no approval queue.
    |
    | SECURITY_TIER is chosen at install time. Tier presets are applied once at
    | boot (see SecurityTier). Individual SECURITY_* env values still override
    | when set explicitly in .env after publish.
    |
    */

    'tier' => env('SECURITY_TIER', 'strict'),

    'tiers' => [
        'strict' => [
            'auth.register' => false,
            'registration.requires_approval' => true,
            'registration.otp_verification' => false,
            'access_provisioning.enabled' => true,
        ],
        'moderate' => [
            'auth.register' => true,
            'registration.requires_approval' => true,
            'registration.otp_verification' => false,
            'access_provisioning.enabled' => true,
        ],
        'minimal' => [
            'auth.register' => true,
            'registration.requires_approval' => false,
            'registration.otp_verification' => true,
            'access_provisioning.enabled' => false,
        ],
    ],

    'registration' => [
        'requires_approval' => true,
        'otp_verification' => false,
        'otp_length' => (int) env('SECURITY_REGISTRATION_OTP_LENGTH', 6),
        'otp_expiry_minutes' => (int) env('SECURITY_REGISTRATION_OTP_EXPIRY', 5),
    ],

    'modes' => [
        'web' => [],
        'api' => [
            'admin.enabled' => false,
        ],
        'hybrid' => [],
    ],

    'guard' => env('SECURITY_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | API / Sanctum
    |--------------------------------------------------------------------------
    |
    | Enable by setting SECURITY_MODE=api or hybrid. Requires laravel/sanctum.
    |
    */

    'api' => [
        'guard' => env('SECURITY_API_GUARD', 'sanctum'),
        'middleware_group' => env('SECURITY_API_MIDDLEWARE_GROUP', 'api'),
        'path_prefix' => env('SECURITY_API_PATH_PREFIX', 'api/security'),
        'route_name_prefix' => env('SECURITY_API_ROUTE_NAME_PREFIX', 'security.api.'),
        'auto_apply_middleware' => (bool) env('SECURITY_API_AUTO_MIDDLEWARE', true),
        'token_idle_timeout_minutes' => (int) env('SECURITY_API_TOKEN_IDLE_MINUTES', 0),
        'token_mfa_verified_ttl_days' => (int) env('SECURITY_API_MFA_TTL_DAYS', 30),
        'revoke_token_on_violation' => (bool) env('SECURITY_API_REVOKE_ON_VIOLATION', true),
        'allowed_route_names' => [
            'security.api.password.status',
            'security.api.password.update',
            'security.api.mfa.status',
            'security.api.mfa.verify',
            'security.api.mfa.resend',
        ],
        'response' => [
            'use_envelope' => (bool) env('SECURITY_API_RESPONSE_ENVELOPE', true),
            'keys' => [
                'code' => 'Code',
                'success' => 'Success',
                'message' => 'Message',
                'description' => 'Description',
                'content' => 'Content',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix & Names
    |--------------------------------------------------------------------------
    |
    | All package routes are prefixed to avoid conflicts with host applications.
    | Example: /security/password/expired → route('security.password.expired')
    |
    */

    'routes' => [
        'prefix' => env('SECURITY_ROUTE_PREFIX', 'security'),
        'name_prefix' => env('SECURITY_ROUTE_NAME_PREFIX', 'security.'),
        'after_mfa_redirect' => env('SECURITY_AFTER_MFA_REDIRECT', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes (login / register / logout / password reset)
    |--------------------------------------------------------------------------
    |
    | Standard Laravel-style auth at app root (/login, /logout).
    | Public self-registration is controlled by SECURITY_TIER / SECURITY_AUTH_REGISTER.
    | strict: registration disabled unless SECURITY_AUTH_REGISTER=true (approval queue).
    | moderate: registration enabled; approval queue before sign-in.
    | minimal: registration enabled with email OTP; account is created after verification.
    | Provision users directly via admin partials regardless of this setting.
    |
    */

    'auth' => [
        'enabled' => (bool) env('SECURITY_AUTH_ROUTES', true),
        'register' => (bool) env('SECURITY_AUTH_REGISTER', false),
        'redirect_after_login' => env('SECURITY_AFTER_LOGIN_REDIRECT', '/security'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PITB SMS Gateway
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'driver' => env('SECURITY_SMS_DRIVER', 'pitb'),
        'disable_send' => (bool) env('SECURITY_SMS_DISABLE_SEND', false),
        'secret_key' => env('SECURITY_SMS_SECRET_KEY'),
        'gateway_url' => env('SECURITY_SMS_GATEWAY_URL', 'https://smsgateway.pitb.gov.pk/api/send_sms'),
        'default_language' => env('SECURITY_SMS_LANGUAGE', 'urdu'),
        'rate_limit_minutes' => (int) env('SECURITY_SMS_RATE_LIMIT_MINUTES', 2),
        'log_table' => env('SECURITY_SMS_LOG_TABLE', 'sms_log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */

    'user' => [
        'model' => env('SECURITY_USER_MODEL', 'App\\Models\\User'),
        'table' => env('SECURITY_USER_TABLE', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */

    'password' => [
        'min_length' => (int) env('SECURITY_PASSWORD_MIN_LENGTH', 12),
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'history_count' => (int) env('SECURITY_PASSWORD_HISTORY', 3),
        'expiry_days' => (int) env('SECURITY_PASSWORD_EXPIRY_DAYS', 90),
        'allowed_routes' => [
            'security.password.expired',
            'security.password.update',
            'security.password.update.submit',
            'logout',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    */

    'lockout' => [
        'max_attempts' => (int) env('SECURITY_LOCKOUT_ATTEMPTS', 5),
        'decay_minutes' => (int) env('SECURITY_LOCKOUT_MINUTES', 30),
        'ip_max_attempts' => (int) env('SECURITY_LOCKOUT_IP_ATTEMPTS', 20),
        'ip_decay_minutes' => (int) env('SECURITY_LOCKOUT_IP_MINUTES', 15),
        'progressive' => [
            5 => 30,
            8 => 120,
            12 => 720,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    */

    'session' => [
        'idle_timeout_minutes' => (int) env('SECURITY_SESSION_IDLE_MINUTES', 20),
        'allowed_routes' => [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'logout',
            'security.mfa.setup',
            'security.mfa.setup.submit',
            'security.mfa.setup.resend',
            'security.mfa.verify',
            'security.mfa.verify.submit',
            'security.mfa.resend',
            'security.password.expired',
            'security.password.update',
            'security.password.update.submit',
        ],
        'allowed_paths' => [
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'security/mfa/setup',
            'security/mfa/setup/resend',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inactive Accounts
    |--------------------------------------------------------------------------
    */

    'inactive_accounts' => [
        'disable_after_days' => (int) env('SECURITY_INACTIVE_DAYS', 60),
        'notify_before_days' => (int) env('SECURITY_INACTIVE_NOTIFY_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    */

    'mfa' => [
        'enabled' => (bool) env('SECURITY_MFA_ENABLED', false),
        'otp_length' => (int) env('SECURITY_MFA_OTP_LENGTH', 6),
        'otp_expiry_minutes' => (int) env('SECURITY_MFA_OTP_EXPIRY', 5),
        'default_method' => env('SECURITY_MFA_DEFAULT_METHOD', 'email'),
        'methods' => array_filter(explode(',', env('SECURITY_MFA_METHODS', 'email,sms'))),
        'allowed_routes' => [
            'security.mfa.setup',
            'security.mfa.setup.submit',
            'security.mfa.setup.resend',
            'security.mfa.verify',
            'security.mfa.verify.submit',
            'security.mfa.resend',
            'logout',
        ],
        'setup_allowed_routes' => [
            'security.mfa.setup',
            'security.mfa.setup.submit',
            'security.mfa.setup.resend',
            'logout',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA
    |--------------------------------------------------------------------------
    |
    | Source of truth for whether CAPTCHA is on. At runtime this is mirrored
    | to captcha.disable (inverted). SECURITY_* keys should be preferred.
    |
    */

    'captcha' => [
        'enabled' => (bool) env('SECURITY_CAPTCHA_ENABLED', true),
        'field' => env('SECURITY_CAPTCHA_FIELD', 'captcha'),
        'profile' => env('SECURITY_CAPTCHA_PROFILE', 'flat'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Provisioning (approval workflow)
    |--------------------------------------------------------------------------
    |
    | Super-admins bypass approval and apply changes immediately. Roles listed
    | under approval_required_roles must submit changes for review. Approver
    | roles can be expanded later via permissions (access-requests.approve).
    |
    */

    'access_provisioning' => [
        'enabled' => (bool) env('SECURITY_ACCESS_PROVISIONING', true),
        'bypass_roles' => ['super-admin'],
        'approval_required_roles' => ['admin'],
        'approver_roles' => ['super-admin', 'admin'],
        'default_temporary_password' => env('SECURITY_DEFAULT_TEMPORARY_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization (Spatie Permission)
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'enabled' => (bool) env('SECURITY_PERMISSIONS_ENABLED', true),
        'guard' => env('SECURITY_PERMISSIONS_GUARD', 'web'),
        'seed_on_install' => (bool) env('SECURITY_PERMISSIONS_SEED', true),
        'default_user_role' => env('SECURITY_DEFAULT_USER_ROLE', 'user'),
        'vendor_role' => 'vendor',
        'privileged_roles' => ['super-admin', 'admin'],

        'permissions' => [
            'security.access',
            'users.view',
            'users.create',
            'users.update',
            'users.disable',
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
            'audit-logs.view',
            'security-reviews.view',
            'security-reviews.record',
            'access-reviews.perform',
            'log-reviews.perform',
            'access-requests.view',
            'access-requests.approve',
            'admin.panel',
        ],

        'roles' => [
            'super-admin' => ['*'],
            'admin' => [
                'security.access',
                'users.view',
                'users.create',
                'users.update',
                'users.disable',
                'roles.view',
                'permissions.view',
                'audit-logs.view',
                'security-reviews.view',
                'security-reviews.record',
                'access-reviews.perform',
                'log-reviews.perform',
                'access-requests.view',
                'access-requests.approve',
                'admin.panel',
            ],
            'manager' => [
                'security.access',
                'users.view',
                'audit-logs.view',
                'access-reviews.perform',
                'log-reviews.perform',
            ],
            'user' => [
                'security.access',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditing Model Options
    |--------------------------------------------------------------------------
    |
    | Source of truth for audit driver. Mirrored to activitylog.enabled and
    | audit.enabled at runtime; SECURITY_* keys should be preferred.
    |
    */

    'auditing' => [
        'driver' => env('SECURITY_AUDIT_DRIVER', 'activitylog'),
        'exclude_attributes' => ['password', 'remember_token'],
        'log_authorization_denials' => (bool) env('SECURITY_LOG_AUTH_DENIED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'retention_months' => (int) env('SECURITY_LOG_RETENTION_MONTHS', 12),
        'minimum_retention_months' => (int) env('SECURITY_LOG_MIN_RETENTION_MONTHS', 3),
        'retention' => [
            'security_events_months' => (int) env('SECURITY_EVENTS_RETENTION_MONTHS', 12),
            'audit_trail_months' => (int) env('SECURITY_AUDIT_RETENTION_MONTHS', 12),
            'security_reviews_months' => (int) env('SECURITY_REVIEWS_RETENTION_MONTHS', 24),
            'access_requests_months' => (int) env('SECURITY_ACCESS_REQUESTS_RETENTION_MONTHS', 24),
        ],
        'redact_keys' => [
            'password',
            'password_confirmation',
            'remember_token',
            'otp',
            'token',
            'secret',
            'captcha',
            'authorization',
            'api_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual Review Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'mail_to' => array_filter(explode(',', env('SECURITY_MAIL_TO', ''))),
        'access_review' => [
            'enabled' => true,
            'interval_months' => (int) env('SECURITY_ACCESS_REVIEW_MONTHS', 6),
            'reminder_cron' => env('SECURITY_ACCESS_REVIEW_CRON', '0 9 1 */6 *'),
        ],
        'log_review' => [
            'enabled' => true,
            'reminder_cron' => env('SECURITY_LOG_REVIEW_CRON', '0 8 * * *'),
        ],
        'inactive_accounts' => [
            'enabled' => true,
            'reminder_cron' => env('SECURITY_INACTIVE_CRON', '0 7 * * 1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'enabled' => (bool) env('SECURITY_ADMIN_ENABLED', true),
        'path_prefix' => env('SECURITY_ADMIN_PATH_PREFIX', 'security/admin/partials'),
        'per_page' => (int) env('SECURITY_ADMIN_PER_PAGE', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS
    |--------------------------------------------------------------------------
    */

    'https' => [
        'force' => (bool) env('SECURITY_FORCE_HTTPS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'auto_apply_web' => (bool) env('SECURITY_AUTO_MIDDLEWARE', true),
        'aliases' => [
            'security.account' => \Pitbphp\Security\Middleware\CheckAccountStatus::class,
            'security.password' => \Pitbphp\Security\Middleware\EnforcePasswordExpiry::class,
            'security.session' => \Pitbphp\Security\Middleware\EnforceSessionTimeout::class,
            'security.token' => \Pitbphp\Security\Middleware\EnforceTokenTimeout::class,
            'security.mfa' => \Pitbphp\Security\Middleware\RequireMfa::class,
            'security.mfa.setup' => \Pitbphp\Security\Middleware\RequireMfaSetup::class,
            'security.https' => \Pitbphp\Security\Middleware\ForceHttps::class,
        ],
    ],

];
