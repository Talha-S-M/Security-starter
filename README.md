# PITB Security Starter

Laravel package implementing PITB Application Security Procedure controls.

## Installation

```bash
composer require pitbphp/security-starter
```

Composer will prompt you to choose an auditing library during install:

| Choice | Package | Best for |
|--------|---------|----------|
| **activitylog** | spatie/laravel-activitylog | Standard apps — key security events only |
| **auditing** | owen-it/laravel-auditing | High-sensitivity apps — full model change history |
| **none** | — | `security_events` table only |

If you skipped the prompt or used `--no-interaction`, run:

```bash
php artisan security:install
```

This publishes config, optional views, sets `SECURITY_AUDIT_DRIVER`, installs the chosen package, and runs migrations.

### Publish customizable views

```bash
php artisan vendor:publish --tag=security-views
```

Views are copied to `resources/views/vendor/security/` where you can edit them freely.

## User model

```php
use Pitbphp\Security\Traits\HasPitbSecurity;

class User extends Authenticatable
{
    use HasPitbSecurity;
}
```

## Optional password history for other models (e.g. Client)

Password history is **always enabled** for authenticated users with `HasPitbSecurity`.

For other models like clients, add the model to config and use the trait:

```php
// config/security.php
'history_models' => [
    \App\Models\Client::class,
],
```

```php
use Pitbphp\Security\Traits\HasPasswordHistory;

class Client extends Model
{
    use HasPasswordHistory;
}
```

```php
use Pitbphp\Security\Rules\PitbPassword;

$request->validate([
    'password' => ['required', 'confirmed', new PitbPassword($client)],
]);

app(PasswordHistoryService::class)->record($client, Hash::make($request->password));
```

## Package routes (no conflicts)

All routes are prefixed under `/security` with the `security.` route name prefix:

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
```

## Manual reviews

```bash
php artisan security:record-access-review --user=1 --notes="Reviewed admin roles"
php artisan security:record-log-review --user=1 --notes="No anomalies"
php artisan security:disable-inactive-users --dry-run
php artisan security:prune-logs
```

## Auditing drivers

Auth events are always stored in `security_events` regardless of driver.

For Owen-It, add `Auditable` to sensitive models. For Activity Log, key events are logged automatically via the package listeners.
