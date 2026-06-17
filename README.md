# PITB Security Starter

Laravel package implementing PITB Application Security Procedure controls.

## Installation

```bash
composer require pitbphp/security-starter
```

Composer will show a message after install. Complete setup with a single command:

```bash
php artisan security:install
```

This will (once):

1. Ask which auditing library to use (`activitylog`, `auditing`, or `none`)
2. Ask which runtime mode to secure (`web`, `api`, or `hybrid`)
3. Install the matching Composer package with a Laravel-compatible version
4. Publish config, views, migrations, and **dependency configs** (`captcha.php`, `permission.php`, `activitylog.php` or `audit.php`)
5. Publish and run auditing package migrations (`activity_log` or `audits` table)
6. Run package and permission migrations
7. Ask whether to seed default PITB roles and permissions

Use `--driver=activitylog --mode=hybrid` to skip prompts, `--skip-seed` to skip RBAC seeding, or `--skip-composer` if you install auditing packages yourself.

If the app has not run baseline Laravel migrations yet (for example `users` table is missing), install automatically runs `php artisan migrate` first as a fallback before package migrations.

If you skip seeding, update `config/security.php` (`security.permissions.permissions` and `security.permissions.roles`) and run:

```bash
php artisan security:seed-rbac
```

Re-publish dependency configs only (without re-running full install):

```bash
php artisan security:publish-vendor-config
# or: php artisan security:publish-vendor-config --driver=activitylog --force
php artisan migrate
```

**`config/security.php` is the source of truth** for feature toggles. Vendor configs (`captcha.php`, `activitylog.php`, `permission.php`, `audit.php`) hold package-specific settings only.

At runtime the package mirrors security settings into vendor config keys so they cannot disagree:

| `security.php` | Mirrored to |
|----------------|-------------|
| `captcha.enabled` | `captcha.disable` (inverted) |
| `auditing.driver` | `activitylog.enabled` / `audit.enabled` |
| `permissions.guard` | `permission.defaults.guard_name` |
| `logging.retention.audit_trail_months` | `activitylog.delete_records_older_than_days` |

Legacy vendor env keys (`CAPTCHA_DISABLE`, `ACTIVITY_LOGGER_ENABLED`, `AUDITING_ENABLED`, `PERMISSION_GUARD`) are no longer required and should be removed when present.

Run `php artisan security:doctor` to detect legacy/env drift and then clear config cache.

### Publish customizable views

```bash
php artisan vendor:publish --tag=security-views
```

Views are copied to `resources/views/vendor/security/` where you can edit them freely.

### Integrate with Laravel default welcome page (optional)

```bash
php artisan security:integrate-welcome
```

This overwrites `resources/views/welcome.blade.php` with a PITB Security dashboard page that includes permission-aware navigation links for users, roles, permissions, logs, reviews, and access requests.

### Publish migrations manually

```bash
php artisan vendor:publish --tag=security-migrations
php artisan migrate --path=database/migrations/pitb_security --realpath
```

## User model

```php
use Pitbphp\Security\Traits\HasPitbSecurity;

class User extends Authenticatable
{
    use HasPitbSecurity;
}
```

`HasPitbSecurity` includes `HasPitbRbac` (Spatie `HasRoles`) and password history support.

### Owen-It full model auditing (optional)

When `SECURITY_AUDIT_DRIVER=auditing`, add to sensitive models:

```php
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Pitbphp\Security\Traits\HasPitbAuditing;

class User extends Authenticatable implements Auditable
{
    use HasPitbSecurity, AuditableTrait, HasPitbAuditing;
}
```

## RBAC (roles & permissions)

Installed by default via Spatie Laravel Permission. `security:install` seeds:

| Role | Purpose |
|------|---------|
| `super-admin` | All permissions |
| `admin` | User/role management, audit logs, security reviews |
| `manager` | View users, perform access/log reviews |
| `user` | Standard application access |
| `vendor` | Third-party time-bound access |

Default permissions include `users.*`, `roles.manage`, `audit-logs.view`, `access-reviews.perform`, `log-reviews.perform`, and more. Customize in `config/security.php` under `permissions`.

```bash
php artisan security:seed-rbac
```

### Route protection

```php
Route::middleware(['auth', 'permission:audit-logs.view'])->group(function () {
    // ...
});

Route::middleware(['role:admin|manager'])->group(function () {
    // ...
});
```

### Automatic logging

- Role/permission assignments → `security_events` + auditing driver
- Denied authorization checks → `authorization.denied` events (when enabled)
- Auth events → existing login/logout/failure logging

## Admin partials (embed in your dashboard)

Publishable Blade **partials only** — no full layout. Embed in your own dashboard or load via routes (iframe/AJAX).

```bash
php artisan vendor:publish --tag=security-admin-views
# → resources/views/vendor/security/admin/partials/
```

### Option 1 — @include in your Blade

```blade
{{-- your-app/resources/views/dashboard/security.blade.php --}}
@extends('layouts.app')

@section('content')
    @include('security::admin.partials.styles') {{-- once per page --}}
    @include('security::admin.partials.nav')

    <h1>Security</h1>
    @include('security::admin.partials.users-table', [
        'users' => $users,
        'filters' => request()->only('search'),
    ])
@endsection
```

Fetch data in your controller using the same queries, or proxy from package services:

```php
use Pitbphp\Security\Services\AuditLogReader;

$events = app(AuditLogReader::class)->securityEvents(request()->only(['event_type', 'success']));
return view('dashboard.security', compact('events'));
```

### Option 2 — Load partial HTML from package routes

Each partial has a route that returns ready-to-embed HTML:

| Partial | Route name | URL |
|---------|------------|-----|
| Summary | `security.admin.partials.summary` | `/security/admin/partials/summary` |
| Security events | `security.admin.partials.security-events` | `/security/admin/partials/security-events` |
| Event detail | `security.admin.partials.security-events.show` | `/security/admin/partials/security-events/{id}` |
| Audit trail | `security.admin.partials.audit-trail` | `/security/admin/partials/audit-trail` |
| Reviews | `security.admin.partials.reviews` | `/security/admin/partials/reviews` |
| Users table | `security.admin.partials.users` | `/security/admin/partials/users` |
| User create | `security.admin.partials.users.create` | `/security/admin/partials/users/create` |
| User form | `security.admin.partials.users.edit` | `/security/admin/partials/users/{id}/edit` |
| Roles table | `security.admin.partials.roles` | `/security/admin/partials/roles` |
| Role form | `security.admin.partials.roles.edit` | `/security/admin/partials/roles/{id}/edit` |
| Permissions | `security.admin.partials.permissions` | `/security/admin/partials/permissions` |
| Access requests | `security.admin.partials.access-requests` | `/security/admin/partials/access-requests` |
| Access request review | `security.admin.partials.access-requests.show` | `/security/admin/partials/access-requests/{id}` |

Example iframe embed:

```blade
<iframe src="{{ route('security.admin.partials.users') }}" class="w-full min-h-screen border-0"></iframe>
```

### Available partials

| Partial | File |
|---------|------|
| Styles (CSS) | `partials/styles.blade.php` |
| Nav links | `partials/nav.blade.php` |
| Dashboard summary | `partials/dashboard-summary.blade.php` |
| Security events | `partials/security-events.blade.php` |
| Event detail | `partials/security-event-detail.blade.php` |
| Audit trail | `partials/audit-trail.blade.php` |
| Manual reviews | `partials/reviews.blade.php` |
| Users table | `partials/users-table.blade.php` |
| User create form | `partials/user-create-form.blade.php` |
| User edit form | `partials/user-form.blade.php` |
| Roles table | `partials/roles-table.blade.php` |
| Role edit form | `partials/role-form.blade.php` |
| Permissions table | `partials/permissions-table.blade.php` |
| Access requests queue | `partials/access-requests.blade.php` |
| Access request review | `partials/access-request-review.blade.php` |

All partials use the `pitb-security` CSS prefix so they won't clash with your styles. Override any file after publishing.

## Authentication routes (login / logout / password reset)

Fresh Laravel has no auth routes — this package registers standard ones at the **app root**:

| URL | Route name |
|-----|------------|
| `/login` | `login` |
| `/logout` (POST) | `logout` |
| `/forgot-password` | `password.request` |
| `/reset-password/{token}` | `password.reset` |

Public self-registration is **disabled by default**. When enabled, it still requires admin approval before the account is created.

```env
SECURITY_AUTH_REGISTER=true
```

Users can request an account at `/register`. An admin (or super-admin) approves it from the access requests queue. Approved users must change password and configure MFA on first login.

Security enforcement routes (password expiry, MFA setup, MFA verify) stay under `/security/*`.

Disable package auth if you add Breeze/Jetstream later:

```env
SECURITY_AUTH_ROUTES=false
```

### Registration (optional, approval required)

Only active when `SECURITY_AUTH_REGISTER=true`. Submissions create a `user_registration` access request — they do not log the user in.

### First login flow (provisioned or approved users)

When an admin creates a user, or approves a registration request:

1. User signs in at `/login`
2. Forced password change (`must_change_password`)
3. MFA setup at `/security/mfa/setup` (MFA email can differ from account email)
4. Normal MFA verification on future logins

Enable MFA for this flow:

```env
SECURITY_MFA_ENABLED=true
SECURITY_MFA_METHODS=email,sms
SECURITY_MFA_DEFAULT_METHOD=email
```

### PITB SMS gateway

MFA SMS uses the PITB gateway (with `sms_log` rate limiting):

```env
SECURITY_SMS_DRIVER=pitb
SECURITY_SMS_SECRET_KEY=your-secret-key
SECURITY_SMS_DISABLE_SEND=false
SECURITY_SMS_LANGUAGE=urdu
SECURITY_SMS_RATE_LIMIT_MINUTES=2
```

For local dev without sending real SMS:

```env
SECURITY_SMS_DRIVER=log
```

Run migrations after update:

```bash
php artisan migrate
```

## CAPTCHA (login)

Publish auth views and wire CAPTCHA into your login form:

```bash
php artisan vendor:publish --tag=security-views
# → resources/views/vendor/security/auth/, mfa/, password/
```

Embed the login partial in your page:

```blade
@include('security::auth.partials.login-form')
```

Validate with the package request class (or add `ValidCaptcha` to your own `LoginRequest`):

```php
use Pitbphp\Security\Http\Requests\SecurityLoginRequest;

public function login(SecurityLoginRequest $request) { ... }
```

Ensure `mews/captcha` is installed. The package ships a safe `captcha.php` (no background-image dependency) and stores optional assets on the `public` disk under `storage/app/public/captcha/backgrounds`.

If you previously published the upstream mews config and see missing `assets/backgrounds` errors, republish:

```bash
php artisan security:publish-vendor-config --force
php artisan storage:link
php artisan config:clear
```

Login/register CAPTCHA includes a refresh button. Set `SECURITY_CAPTCHA_ENABLED=true` in `.env`.

## MFA (email OTP)

Enable with `SECURITY_MFA_ENABLED=true`. After login, middleware redirects to `/security/mfa/verify`.

Publish and customize the verify form:

```blade
@include('security::mfa.partials.verify-form')
```

Failed OTP attempts count toward account lockout (`SECURITY_LOCKOUT_ATTEMPTS`).

Lockout is progressive by default (`5 => 30min`, `8 => 120min`, `12 => 720min`) and can also track repeated failures by `IP + email` combination.

## Access provisioning (approval workflow)

When `SECURITY_ACCESS_PROVISIONING=true` (default):

| Actor | Behaviour |
|-------|-----------|
| **super-admin** | Applies user/role changes immediately; sees pending queue on dashboard |
| **admin** | Must submit changes with justification; super-admin approves or rejects |

Configure roles in `config/security.php` under `access_provisioning`:

```php
'bypass_roles' => ['super-admin'],
'approval_required_roles' => ['admin'],
'approver_roles' => ['super-admin'],
```

Later you can add approver roles and grant `access-requests.approve` without code changes.

Run migrations after update:

```bash
php artisan migrate
php artisan security:seed-rbac   # adds access-requests.* permissions
```

Super-admins see **Pending access approvals** on the dashboard summary partial and can review at `/security/admin/partials/access-requests`.

## Password history

Password reuse history is enabled for authenticated users via `HasPitbSecurity` only.

## Password strength (client-side)

Live password feedback runs entirely in the browser — no per-keystroke server requests. Policy values from `config/security.php` are embedded once in the page as JSON; validation on submit still uses the `PitbPassword` rule server-side.

**Blade partial** — use on any form with password + confirmation fields:

```blade
@include('security::auth.partials.password-fields')
```

Optional: `passwordLabel`, `confirmationLabel`, `passwordId`, `confirmationId`, `passwordAutocomplete`, `confirmationAutocomplete`.

Already included on register, reset-password, password update, and admin user-create forms.

**Lower-level partial** (meter only, if you render inputs yourself):

```blade
@include('security::auth.partials.password-strength')
```

**Standalone JS** (e.g. custom SPA or Vite bundle):

```html
<script src="{{ asset('vendor/pitb-security/js/pitb-password-strength.js') }}" defer></script>
<script>
  const result = PitbPasswordStrength.analyze('MyPass123!', 'MyPass123!', {
    min_length: 12,
    require_uppercase: true,
    require_lowercase: true,
    require_numbers: true,
    require_symbols: true,
  });
  // result.valid, result.strength, result.score, result.rules
</script>
```

Or bind to a widget:

```html
<div data-pitb-password-strength data-password-id="password" data-policy='@json(\Pitbphp\Security\Support\PasswordStrength::policy())'>
  ...
</div>
```

Publish static assets:

```bash
php artisan vendor:publish --tag=security-assets
# → public/vendor/pitb-security/js/pitb-password-strength.js
```

`security:install` publishes this tag automatically. Until then, the script is served from `/security/assets/password-strength.js`.

## Package routes (no conflicts)

All routes are prefixed under `/security` with the `security.` route name prefix:

- Main page: `security.home` (`/security`) with a built-in header partial
- Header partial: `security::partials.header` (shows `Login`/`Logout` and permission-based links for Users, Roles, Permissions, and Activity log)

| URL | Route name |
|-----|------------|
| `/security/password/expired` | `security.password.expired` |
| `/security/password/update` | `security.password.update` |
| `/security/mfa/verify` | `security.mfa.verify` |

Override prefix in `.env`:

```env
SECURITY_ROUTE_PREFIX=security
SECURITY_ROUTE_NAME_PREFIX=security.
```

## API / Sanctum mode (optional)

By default the package runs in **web** mode (sessions). For API-first apps, enable Sanctum support:

```bash
composer require laravel/sanctum
```

```env
SECURITY_MODE=hybrid   # web | api | hybrid
SECURITY_API_GUARD=sanctum
SECURITY_API_TOKEN_IDLE_MINUTES=20
```

| Mode | Behaviour |
|------|-----------|
| `web` | Session middleware on `web` group only (default) |
| `api` | Token middleware on `api` group only |
| `hybrid` | Both — API vs web detected per request (Bearer token = API) |

### API routes

| Method | URL | Route name |
|--------|-----|------------|
| GET | `/api/security/password/status` | `security.api.password.status` |
| POST | `/api/security/password/update` | `security.api.password.update` |
| POST | `/api/security/mfa/verify` | `security.api.mfa.verify` |
| POST | `/api/security/mfa/resend` | `security.api.mfa.resend` |

API security violations return JSON with an `error_code` (e.g. `password_expired`, `mfa_required`, `account_locked`). Tokens are revoked on account violations when `SECURITY_API_REVOKE_ON_VIOLATION=true`.
In `api` or `hybrid` mode, middleware auto-detects API requests (Bearer token, API path, API route names, or JSON expectation) and consistently returns JSON errors.

### API response envelope (optional)

Security API responses can follow an envelope style similar to:
`Code`, `Success`, `Message`, `Description`, `Content`.

```env
SECURITY_API_RESPONSE_ENVELOPE=true
```

Configure keys in `config/security.php`:

```php
'api' => [
    'response' => [
        'use_envelope' => true,
        'keys' => [
            'code' => 'Code',
            'success' => 'Success',
            'message' => 'Message',
            'description' => 'Description',
            'content' => 'Content',
        ],
    ],
],
```

### Example API flow

```bash
# 1. Login and receive Sanctum token (your app login route)
# 2. If MFA enabled, verify OTP:
curl -X POST /api/security/mfa/verify \
  -H "Authorization: Bearer {token}" \
  -d "otp=123456"

# 3. If password expired:
curl -X POST /api/security/password/update \
  -H "Authorization: Bearer {token}" \
  -d "password=NewPass123!&password_confirmation=NewPass123!"
```

## Environment

```env
SECURITY_MODE=web
SECURITY_MAIL_TO=security@example.com,admin@example.com
SECURITY_AUDIT_DRIVER=activitylog
SECURITY_PASSWORD_EXPIRY_DAYS=90
SECURITY_SESSION_IDLE_MINUTES=20
SECURITY_MFA_ENABLED=false
SECURITY_CAPTCHA_ENABLED=true
SECURITY_ACCESS_PROVISIONING=true
SECURITY_SMS_DRIVER=pitb
SECURITY_SMS_SECRET_KEY=
SECURITY_SMS_DISABLE_SEND=false
SECURITY_SMS_RATE_LIMIT_MINUTES=2
SECURITY_LOCKOUT_ATTEMPTS=5
SECURITY_LOCKOUT_MINUTES=30
SECURITY_LOCKOUT_IP_ATTEMPTS=20
SECURITY_LOCKOUT_IP_MINUTES=15
SECURITY_EVENTS_RETENTION_MONTHS=12
SECURITY_AUDIT_RETENTION_MONTHS=12
SECURITY_REVIEWS_RETENTION_MONTHS=24
SECURITY_ACCESS_REQUESTS_RETENTION_MONTHS=24
```

## Manual reviews

```bash
php artisan security:record-access-review --user=1 --notes="Reviewed admin roles"
php artisan security:record-log-review --user=1 --notes="No anomalies"
php artisan security:disable-inactive-users --dry-run
php artisan security:prune-logs
php artisan security:doctor
```

## Auditing drivers

Auth events are always stored in `security_events` regardless of driver.

For Owen-It, add `Auditable` to sensitive models. For Activity Log, key events are logged automatically via the package listeners.
