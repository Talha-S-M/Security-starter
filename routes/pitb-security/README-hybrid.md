# PITB Security routes (hybrid mode)

Published for `SECURITY_MODE=hybrid`. Web **and** API route files are copied to this folder.

When a file exists here, it **replaces** the package default of the same name.

## Discover routes

```bash
php artisan security:routes
php artisan security:routes --group=api
php artisan security:routes --group=web
php artisan route:list --name=security
```

## Published files

| File | Purpose |
|------|---------|
| `security-api-auth.php` | API login/logout/register (Sanctum) |
| `security-api.php` | API password + MFA |
| `auth.php` | Web login/logout/register (sessions) |
| `security.php` | Web MFA, password expiry, home |
| `security-admin.php` | Admin partials |

## API (Bearer token)

See API tables in `README-api.md` — same endpoints apply.

Sanctum SPA cookie route (package default, not in this folder):

| Method | Path | Route name |
|--------|------|------------|
| GET | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` |

## Web (session)

See web tables in `README-web.md` — same endpoints apply.

Request context (API vs web) is detected per request (Bearer token, JSON, API path, etc.).

Do not register these files in `bootstrap/app.php` — the package loads them automatically.
