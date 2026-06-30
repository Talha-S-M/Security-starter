# PITB Security routes (web mode)

Published for `SECURITY_MODE=web`. Only web/session route files are copied to this folder.

When a file exists here, it **replaces** the package default of the same name.

## Discover routes

```bash
php artisan security:routes
php artisan route:list --name=security
```

## Published files

| File | Purpose |
|------|---------|
| `auth.php` | `/login`, `/logout`, `/register` |
| `security.php` | MFA, password expiry, package home |
| `security-admin.php` | Admin partials (when `admin.enabled`) |

## Common web routes

| Route name | Path (default) | Purpose |
|------------|----------------|---------|
| `login` | `/login` | Session login |
| `logout` | `/logout` | End session |
| `register` | `/register` | Self-registration (tier-dependent) |
| `security.home` | `/security` | Package home |
| `security.mfa.verify` | `/security/mfa/verify` | MFA OTP |
| `security.mfa.setup` | `/security/mfa/setup` | MFA setup |
| `security.password.expired` | `/security/password/expired` | Forced password change |

Prefix: `SECURITY_ROUTE_PREFIX` in `config/security.php`.

Do not register these files in `bootstrap/app.php` — the package loads them automatically.
