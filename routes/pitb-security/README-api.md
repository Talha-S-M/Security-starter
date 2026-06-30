# PITB Security routes (API mode)

Published for `SECURITY_MODE=api`. Only API route files are copied to this folder.

When a file exists here, it **replaces** the package default of the same name.

## Discover routes

```bash
php artisan security:routes
php artisan security:routes --json
php artisan route:list --name=security
```

## Published files

| File | Purpose |
|------|---------|
| `security-api-auth.php` | Login, logout, register (Sanctum tokens) |
| `security-api.php` | Password + MFA enforcement endpoints |

## Auth routes (`security-api-auth.php`)

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

## Security enforcement (`security-api.php`)

| Method | Path | Route name | Purpose |
|--------|------|------------|---------|
| GET | `/api/security/password/status` | `security.api.password.status` | Password state |
| POST | `/api/security/password/update` | `security.api.password.update` | Change expired password |
| GET | `/api/security/mfa/status` | `security.api.mfa.status` | MFA state |
| POST | `/api/security/mfa/verify` | `security.api.mfa.verify` | Submit MFA OTP |
| POST | `/api/security/mfa/resend` | `security.api.mfa.resend` | Resend MFA OTP |

Middleware may return JSON errors: `password_expired`, `mfa_required`, `mfa_setup_required`, `account_locked`.

Prefixes: `SECURITY_API_AUTH_PATH_PREFIX`, `SECURITY_API_PATH_PREFIX` in `config/security.php`.

Do not register these files in `bootstrap/app.php` — the package loads them automatically.
