{{-- Shared styles for admin partials and auth pages. Publish and @include once per page. --}}
<style>
    .pitb-security { font-family: system-ui, -apple-system, sans-serif; font-size: .875rem; color: #0f172a; box-sizing: border-box; }
    .pitb-security *, .pitb-security *::before, .pitb-security *::after { box-sizing: inherit; }

    .pitb-security-page { background: #f1f5f9; min-height: 100vh; margin: 0; padding: 1.25rem; }
    .pitb-security .topbar { margin-bottom: 1.5rem; }
    .pitb-security .topbar nav a { color: #2563eb; text-decoration: none; margin-right: 1rem; font-weight: 500; }
    .pitb-security .topbar nav a:hover { text-decoration: underline; }

    .pitb-security .auth-shell { max-width: 28rem; margin: 0 auto; }
    .pitb-security .auth-card { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1.5rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .06); }
    .pitb-security .auth-card h1 { margin: 0 0 1rem; font-size: 1.5rem; line-height: 1.2; }

    .pitb-security .card { background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: 1rem; }
    .pitb-security table { width: 100%; border-collapse: collapse; }
    .pitb-security th, .pitb-security td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .pitb-security th { background: #f8fafc; font-weight: 600; }
    .pitb-security .muted { color: #64748b; }
    .pitb-security .badge { display: inline-block; padding: .125rem .5rem; border-radius: 999px; font-size: .75rem; }
    .pitb-security .badge-success { background: #dcfce7; color: #166534; }
    .pitb-security .badge-danger { background: #fee2e2; color: #991b1b; }
    .pitb-security .btn { display: inline-block; padding: .5rem .875rem; border-radius: .375rem; border: 0; cursor: pointer; font-size: .875rem; text-decoration: none; line-height: 1.25; }
    .pitb-security .btn-primary { background: #2563eb; color: #fff; }
    .pitb-security .btn-primary:hover { background: #1d4ed8; }
    .pitb-security .btn-secondary { background: #e2e8f0; color: #0f172a; }
    .pitb-security .btn-block { width: 100%; text-align: center; margin-top: .25rem; }
    .pitb-security .filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .pitb-security .filters input, .pitb-security .filters select { padding: .4rem .5rem; border: 1px solid #e2e8f0; border-radius: .375rem; }
    .pitb-security .status { padding: .5rem .75rem; background: #ecfdf5; color: #065f46; border-radius: .375rem; margin-bottom: .75rem; }
    .pitb-security .errors { color: #dc2626; margin: 0 0 .75rem; padding-left: 1.25rem; }
    .pitb-security pre.json { background: #f1f5f9; padding: .75rem; border-radius: .375rem; overflow: auto; font-size: .75rem; }

    .pitb-security label { display: block; font-weight: 600; margin: 0 0 .35rem; }
    .pitb-security .field { margin-bottom: .9rem; }
    .pitb-security input[type="email"],
    .pitb-security input[type="password"],
    .pitb-security input[type="text"],
    .pitb-security input[type="tel"],
    .pitb-security textarea,
    .pitb-security select {
        width: 100%;
        padding: .55rem .75rem;
        border: 1px solid #cbd5e1;
        border-radius: .375rem;
        font: inherit;
        background: #fff;
    }
    .pitb-security input:focus, .pitb-security textarea:focus, .pitb-security select:focus {
        outline: 2px solid #93c5fd;
        border-color: #60a5fa;
    }
    .pitb-security .checkbox-group label { font-weight: 400; display: flex; align-items: center; gap: .5rem; margin: .25rem 0; }
    .pitb-security nav a { display: inline-block; margin-right: .75rem; margin-bottom: .5rem; }
    .pitb-security .captcha-wrap { margin: .25rem 0 .5rem; }
    .pitb-security .auth-links { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; font-size: .875rem; }
    .pitb-security .auth-links a { color: #2563eb; text-decoration: none; }
    .pitb-security .auth-links a:hover { text-decoration: underline; }
</style>
