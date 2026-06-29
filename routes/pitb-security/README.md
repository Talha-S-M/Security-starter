# PITB Security routes

This folder is published by `security:install` (tag `security-routes`).  
When a file exists here, it **replaces** the package default of the same name.

## Discover routes at runtime

```bash
php artisan security:routes
php artisan security:routes --json
php artisan security:routes --group="api"
```

Or use Laravel's route list:

```bash
php artisan route:list --name=security
```

## API mode (`SECURITY_MODE=api`)

### Auth (`routes/pitb-security/security-api-auth.php`)

| Method | Path | Route name | Auth |
|--------|------|------------|------|
| POST | `/api/login` | `security.api.login` | No |
| POST | `/api/logout` | `security.api.logout` | Bearer token |
| POST | `/api/register` | `security.api.register` | No (tier-dependent) |
| POST | `/api/register/verify` | `security.api.register.verify` | No (minimal tier) |
| POST | `/api/register/resend` | `security.api.register.resend` | No (minimal tier) |

**Login example**

```http
POST /api/login
Content-Type: application/json

{"email":"user@example.com","password":"secret","device_name":"mobile"}
```

Response includes `token` — use `Authorization: Bearer {token}` on subsequent requests.

### Security enforcement (`routes/pitb-security/security-api.php`)

| Method | Path | Route name | Purpose |
|--------|------|------------|---------|
| GET | `/api/security/password/status` | `security.api.password.status` | Password state |
| POST | `/api/security/password/update` | `security.api.password.update` | Change expired password |
| GET | `/api/security/mfa/status` | `security.api.mfa.status` | MFA state |
| POST | `/api/security/mfa/verify` | `security.api.mfa.verify` | Submit MFA OTP |
| POST | `/api/security/mfa/resend` | `security.api.mfa.resend` | Resend MFA OTP |

Middleware may return JSON errors before your app routes run, e.g. `password_expired`, `mfa_required`, `mfa_setup_required`, `account_locked`.

### Sanctum default (hybrid SPA)

| Method | Path | Route name |
|--------|------|------------|
| GET | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` |

Registered by Laravel Sanctum — not defined in this package.

## Web / hybrid mode

| File | Purpose |
|------|---------|
| `auth.php` | `/login`, `/logout`, `/register` |
| `security.php` | MFA, password expiry, package home |
| `security-admin.php` | Admin partials (when enabled) |

Prefixes are configurable in `config/security.php` (`SECURITY_ROUTE_PREFIX`, `SECURITY_API_PATH_PREFIX`, etc.).

## Customizing

1. Publish (if missing): `php artisan vendor:publish --tag=security-routes`
2. Edit files in `routes/pitb-security/`
3. Run `php artisan route:clear` if routes are cached

Do not register these files manually in `bootstrap/app.php` — the package loads them automatically when present.
