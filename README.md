# PITB Security Starter

Laravel package implementing PITB Application Security Procedure controls.

## Installation

```bash
composer require pitbphp/security-starter
```

After the **first** `composer require`, Composer will ask whether to run setup now (interactive terminals only). If you confirm, you choose auditing driver, runtime mode, and security tier once in Composer; those choices are passed to `security:install` with `--no-interaction` so you are not prompted again. Run `php artisan security:install` directly anytime for the full interactive wizard.

```bash
php artisan security:install
```

A marker file is written to `storage/app/pitb-security-installed` when install completes. Existing setups with `config/security.php` are also treated as already installed.

This will (once):

1. Ask which auditing library to use (`activitylog`, `auditing`, or `none`)
2. Ask which runtime mode to secure (`web`, `api`, or `hybrid`)
3. Install the matching Composer package with a Laravel-compatible version
4. Publish config, migrations, and **dependency configs** (`captcha.php`, `permission.php`, `activitylog.php` or `audit.php`). Views and front-end assets are published only for `web` and `hybrid` — **not** for `api` mode.
5. Publish and run auditing package migrations (`activity_log` or `audits` table)
6. Run package and permission migrations
7. Ask whether to seed default PITB roles and permissions

Use `--driver=activitylog --mode=hybrid` to skip prompts, `--mode=api` for JSON-only API apps (no Blade views), `--skip-seed` to skip RBAC seeding, or `--skip-composer` if you install auditing packages yourself.

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

### Publish customizable views (web / hybrid only)

Skip this section when `SECURITY_MODE=api` — API mode does not load or require Blade views.

```bash
php artisan vendor:publish --tag=security-views
```
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
| Audit trail | `partials/audit-trail.blade.php` (includes a **Details** column summarising what changed) |
| Manual reviews | `partials/reviews.blade.php` |
| Users table | `partials/users-table.blade.php` |
| User create form | `partials/user-create-form.blade.php` (prefills a policy-compliant temporary password; optional `SECURITY_DEFAULT_TEMPORARY_PASSWORD`) |
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

Public self-registration is controlled by **security tier** (`SECURITY_TIER`) and optional `SECURITY_AUTH_REGISTER`.

| Tier | Registration | Approval | OTP | Admin provisioning |
|------|--------------|----------|-----|-------------------|
| `strict` (default) | Off | N/A (no public sign-up) | No | Yes — admin changes need super-admin approval |
| `moderate` | On | Yes — access request queue | No | Yes — same approval queue |
| `minimal` | On | No — instant after email OTP | Yes | No — changes apply immediately |

```env
SECURITY_TIER=moderate
```

**Strict:** only super-admin/admin create users from the admin panel. If an admin creates or updates a user, a super-admin must approve the access request.

**Moderate:** anyone can request an account at `/register`; an admin or super-admin approves from the access requests queue before the user can sign in.

**Minimal:** users register at `/register`, receive an email OTP, verify, and are signed in immediately with the default `user` role (MFA setup on first login still applies when `SECURITY_MFA_ENABLED=true`).

Choose tier at install:

```bash
php artisan security:install --tier=moderate
```

Tier presets are applied once at boot (`SecurityTier`); feature code reads flat config keys instead of branching everywhere.

Security enforcement routes (password expiry, MFA setup, MFA verify) stay under `/security/*`.

Disable package auth if you add Breeze/Jetstream later:

```env
SECURITY_AUTH_ROUTES=false
```

### Registration (tier-dependent)

Controlled by `SECURITY_TIER` (see table above). **Moderate** and **strict** (with `SECURITY_AUTH_REGISTER=true`) create a `user_registration` access request — they do not log the user in. **Minimal** uses a two-step OTP flow at `/register` → verify → auto login.

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

**Web / hybrid only.** Publish auth views and wire CAPTCHA into your login form:

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

Active when `SECURITY_TIER` is `strict` or `moderate` (default: `strict`). **Minimal tier disables the approval queue** — admin and public registration changes apply immediately.

When `security.access_provisioning.enabled` is true:

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

Admin user create also loads `pitb-temporary-password.js` for generate, preview, and copy-to-clipboard (`showGeneratePassword` on the password-fields partial).

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
# → public/vendor/pitb-security/js/pitb-temporary-password.js
```

`security:install` publishes this tag automatically. Until then, scripts are served from `/security/assets/password-strength.js` and `/security/assets/temporary-password.js`.

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

For API-first apps, `security:install --mode=api` (or `hybrid`) installs **laravel/sanctum**, publishes its config/migrations, and registers token auth routes.

```bash
php artisan security:install --mode=api
```

```env
SECURITY_MODE=api   # web | api | hybrid
SECURITY_API_GUARD=sanctum
SECURITY_API_TOKEN_IDLE_MINUTES=20
SECURITY_API_AUTH_ROUTES=true
```

| Mode | Behaviour |
|------|-----------|
| `web` | Session middleware on `web` group; Blade views, login/register, admin partials |
| `api` | Sanctum tokens on `api` group; **JSON only — views are not loaded or published** |
| `hybrid` | Both — Bearer token = API; browser session = web |

Add `HasApiTokens` to your User model (install warns if missing):

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasPitbSecurity;
}
```

### Sanctum routes

| Method | URL | Route name | Purpose |
|--------|-----|------------|---------|
| GET | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | Sanctum default — SPA cookie auth (hybrid) |
| POST | `/api/login` | `security.api.login` | Email/password → Bearer token |
| POST | `/api/logout` | `security.api.logout` | Revoke current token |
| POST | `/api/register` | `security.api.register` | Tier-dependent registration (JSON) |
| POST | `/api/register/verify` | `security.api.register.verify` | OTP verify (`minimal` tier) |
| POST | `/api/register/resend` | `security.api.register.resend` | Resend registration OTP |

In **`api` mode** you do **not** need `vendor:publish --tag=security-views`. Security middleware violations (password expired, MFA required, account locked, etc.) return JSON via `SecurityResponder`.

### Discover API routes

After install, route files and a reference guide are published to `routes/pitb-security/`:

```bash
php artisan security:routes
php artisan security:routes --json
php artisan route:list --name=security
```

See `routes/pitb-security/README.md` for method/path/name tables and login examples.

Publish manually anytime:

```bash
php artisan vendor:publish --tag=security-routes
```

Published route files override package defaults when present (same filenames under `routes/pitb-security/`).

### API routes

| Method | URL | Route name |
|--------|-----|------------|
| GET | `/api/security/password/status` | `security.api.password.status` |
| POST | `/api/security/password/update` | `security.api.password.update` |
| GET | `/api/security/mfa/status` | `security.api.mfa.status` |
| POST | `/api/security/mfa/verify` | `security.api.mfa.verify` |
| POST | `/api/security/mfa/resend` | `security.api.mfa.resend` |

API security violations return JSON with an `error_code` in the `Description` field (e.g. `password_expired`, `mfa_required`, `mfa_setup_required`, `account_locked`). Tokens are revoked on account violations when `SECURITY_API_REVOKE_ON_VIOLATION=true`.
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
# 1. Login and receive Sanctum token
curl -X POST /api/login -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","device_name":"mobile"}'

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
SECURITY_TIER=strict
SECURITY_MAIL_TO=security@example.com,admin@example.com
SECURITY_AUDIT_DRIVER=activitylog
SECURITY_PASSWORD_EXPIRY_DAYS=90
SECURITY_SESSION_IDLE_MINUTES=20
SECURITY_MFA_ENABLED=false
SECURITY_CAPTCHA_ENABLED=true
SECURITY_ACCESS_PROVISIONING=true
SECURITY_DEFAULT_TEMPORARY_PASSWORD=
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

Package events are split across two stores with **no duplication**:

| Destination | What is stored |
|-------------|----------------|
| **Security Events** (`security_events`) | Login, logout, failed login, MFA, password changes/resets, registration submitted, authorization denials, account lockouts |
| **Audit Trail** (Spatie `activity_log` or Owen-It `audits`) | User create/update, role permission changes, RBAC attach/detach, access request workflow, manual reviews, plus your app's own audit entries |

`SECURITY_AUDIT_DRIVER` selects where the audit trail is read from (`activitylog`, `auditing`, or `none`). Auth events always go to `security_events` only; RBAC and provisioning events go to the audit driver only.

**Security Events** (`/security/admin/partials/security-events`) — authentication and access-control activity.

**Audit Trail** (`/security/admin/partials/audit-trail`) — provisioning, RBAC, and application audit records from Spatie Activity Log or Owen-It.

> With `SECURITY_AUDIT_DRIVER=none`, RBAC/provisioning events are not persisted. Use `activitylog` or `auditing` if you need an audit trail.

### Model change auditing (Owen-It)

When `SECURITY_AUDIT_DRIVER=auditing`, add `Auditable` to sensitive models (for example `User`, `Order`). Attribute changes are recorded automatically:

```php
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Pitbphp\Security\Traits\HasPitbAuditing;

class Order extends Model implements Auditable
{
    use AuditableTrait, HasPitbAuditing;
}
```

```php
// Persisting a change is enough — Owen-It writes the audit row
$order->update([
    'status' => 'delivered',
    'delivered_at' => now(),
]);
```

View results under **Audit trail** in the admin partials.

### Custom business events (Spatie Activity Log)

When `SECURITY_AUDIT_DRIVER=activitylog`, log domain actions (order delivered, payment captured, etc.) with Spatie's `activity()` helper. Use a log name per domain (`orders`, `payments`, …) and dot-separated event descriptions:

```php
use App\Models\Order;

public function markDelivered(Order $order): void
{
    $order->update([
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    activity('orders')
        ->performedOn($order)
        ->causedBy(auth()->user())
        ->withProperties([
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
        ])
        ->log('order.delivered');
}
```

Optional: add Spatie's `LogsActivity` trait to a model if you want automatic logging on create/update/delete in addition to manual entries.

### Choosing an approach

| Goal | Where it is stored |
|------|-------------------|
| Login, logout, failed login, MFA, passwords | `security_events` → Security Events UI |
| User/role changes, access requests, RBAC | Audit driver → Audit Trail UI |
| “Who changed this model field?” | Owen-It `Auditable` on the model (Audit Trail) |
| “Something happened” (delivered, approved, exported) | Spatie `activity()` with `performedOn` / `causedBy` (Audit Trail) |
| Periodic compliance reviews | `security_reviews` → Reviews UI |

Do not log the same package event twice — auth stays in Security Events; provisioning and RBAC stay in the audit trail.
