{{-- Shared styles for admin partials and auth pages. Publish and @include once per page. --}}
<style>
    :root {
        --pitb-bg: #f1f5f9;
        --pitb-surface: #ffffff;
        --pitb-border: #e2e8f0;
        --pitb-border-strong: #cbd5e1;
        --pitb-text: #0f172a;
        --pitb-muted: #64748b;
        --pitb-primary: #2563eb;
        --pitb-primary-hover: #1d4ed8;
        --pitb-primary-soft: #dbeafe;
        --pitb-success: #166534;
        --pitb-success-bg: #dcfce7;
        --pitb-danger: #991b1b;
        --pitb-danger-bg: #fee2e2;
        --pitb-warning-bg: #fef3c7;
        --pitb-warning-text: #92400e;
        --pitb-radius: .75rem;
        --pitb-radius-sm: .5rem;
        --pitb-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .04);
        --pitb-sidebar-width: 15.5rem;
    }

    .pitb-security { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: .9375rem; color: var(--pitb-text); box-sizing: border-box; line-height: 1.5; }
    .pitb-security *, .pitb-security *::before, .pitb-security *::after { box-sizing: inherit; }

    .pitb-security-page { background: var(--pitb-bg); min-height: 100vh; margin: 0; }

    /* Admin shell */
    .pitb-security .admin-app { display: grid; grid-template-columns: var(--pitb-sidebar-width) minmax(0, 1fr); min-height: 100vh; }
    .pitb-security .admin-sidebar {
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        color: #e2e8f0;
        padding: 1.25rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        border-right: 1px solid rgba(255, 255, 255, .06);
    }
    .pitb-security .admin-brand { padding: .25rem .5rem .75rem; border-bottom: 1px solid rgba(255, 255, 255, .08); margin-bottom: .25rem; }
    .pitb-security .admin-brand__title { margin: 0; font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -.02em; }
    .pitb-security .admin-brand__subtitle { margin: .25rem 0 0; font-size: .75rem; color: #94a3b8; }
    .pitb-security .admin-nav { display: flex; flex-direction: column; gap: .25rem; flex: 1; }
    .pitb-security .admin-nav__section { margin: .75rem 0 .35rem; padding: 0 .5rem; font-size: .6875rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
    .pitb-security .admin-nav a {
        display: block;
        padding: .625rem .75rem;
        border-radius: .5rem;
        color: #cbd5e1;
        text-decoration: none;
        font-weight: 500;
        transition: background .15s ease, color .15s ease;
    }
    .pitb-security .admin-nav a:hover { background: rgba(255, 255, 255, .08); color: #fff; }
    .pitb-security .admin-nav a.is-active { background: rgba(37, 99, 235, .22); color: #fff; box-shadow: inset 0 0 0 1px rgba(96, 165, 250, .35); }
    .pitb-security .admin-sidebar__footer { margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, .08); }
    .pitb-security .admin-sidebar__footer .btn { width: 100%; text-align: center; }

    .pitb-security .admin-main { padding: 1.5rem 1.75rem 2rem; min-width: 0; }
    .pitb-security .page-header { margin-bottom: 1.25rem; }
    .pitb-security .page-header h1 { margin: 0; font-size: 1.75rem; line-height: 1.2; letter-spacing: -.02em; }
    .pitb-security .page-subtitle { margin: .35rem 0 0; color: var(--pitb-muted); font-size: .9375rem; }
    .pitb-security .page-body { max-width: 72rem; }

    /* Legacy topbar (home/auth) */
    .pitb-security .topbar {
        background: var(--pitb-surface);
        border: 1px solid var(--pitb-border);
        border-radius: var(--pitb-radius);
        padding: .75rem 1rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--pitb-shadow);
    }
    .pitb-security .topbar nav { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; }
    .pitb-security .topbar nav a { color: var(--pitb-primary); text-decoration: none; font-weight: 600; }
    .pitb-security .topbar nav a:hover { text-decoration: underline; }

    .pitb-security .auth-shell { max-width: 32rem; margin: 0 auto; padding: 1.25rem; }
    .pitb-security .auth-card,
    .pitb-security .card,
    .pitb-security .panel {
        background: var(--pitb-surface);
        border: 1px solid var(--pitb-border);
        border-radius: var(--pitb-radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--pitb-shadow);
    }
    .pitb-security .auth-card h1 { margin: 0 0 1rem; font-size: 1.5rem; line-height: 1.2; }
    .pitb-security .panel + .panel { margin-top: 1rem; }
    .pitb-security .panel__title { margin: 0 0 1rem; font-size: 1.05rem; font-weight: 700; }

    .pitb-security .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .pitb-security .stat-card { background: var(--pitb-surface); border: 1px solid var(--pitb-border); border-radius: var(--pitb-radius); padding: 1rem 1.1rem; box-shadow: var(--pitb-shadow); }
    .pitb-security .stat-card__label { color: var(--pitb-muted); font-size: .8125rem; margin-bottom: .35rem; }
    .pitb-security .stat-card__value { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; }

    .pitb-security table { width: 100%; border-collapse: collapse; }
    .pitb-security .table-wrap { overflow-x: auto; }
    .pitb-security th, .pitb-security td { text-align: left; padding: .75rem .9rem; border-bottom: 1px solid var(--pitb-border); vertical-align: top; }
    .pitb-security th { background: #f8fafc; font-weight: 600; font-size: .8125rem; color: #475569; text-transform: uppercase; letter-spacing: .04em; }
    .pitb-security tbody tr:hover { background: #f8fafc; }
    .pitb-security .table-link { color: var(--pitb-primary); font-weight: 600; text-decoration: none; }
    .pitb-security .table-link:hover { text-decoration: underline; }

    .pitb-security .muted { color: var(--pitb-muted); }
    .pitb-security .badge { display: inline-flex; align-items: center; padding: .2rem .55rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
    .pitb-security .badge-success { background: var(--pitb-success-bg); color: var(--pitb-success); }
    .pitb-security .badge-danger { background: var(--pitb-danger-bg); color: var(--pitb-danger); }
    .pitb-security .badge-neutral { background: #e2e8f0; color: #475569; }

    .pitb-security .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        padding: .55rem .95rem; border-radius: var(--pitb-radius-sm); border: 0; cursor: pointer;
        font-size: .875rem; font-weight: 600; text-decoration: none; line-height: 1.25;
        transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
    }
    .pitb-security .btn:hover { transform: translateY(-1px); }
    .pitb-security .btn-primary { background: var(--pitb-primary); color: #fff; box-shadow: 0 1px 2px rgba(37, 99, 235, .25); }
    .pitb-security .btn-primary:hover { background: var(--pitb-primary-hover); }
    .pitb-security .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
    .pitb-security .btn-secondary { background: #e2e8f0; color: var(--pitb-text); }
    .pitb-security .btn-secondary:hover { background: #cbd5e1; }
    .pitb-security .btn-danger { background: #dc2626; color: #fff; }
    .pitb-security .btn-danger:hover { background: #b91c1c; }
    .pitb-security .btn-block { width: 100%; }
    .pitb-security .btn-sm { padding: .4rem .7rem; font-size: .8125rem; }

    .pitb-security .toolbar,
    .pitb-security .filters { display: flex; flex-wrap: wrap; gap: .65rem; align-items: center; margin-bottom: 1rem; }
    .pitb-security .filters input,
    .pitb-security .filters select { min-width: 10rem; }

    .pitb-security .alert { padding: .75rem 1rem; border-radius: var(--pitb-radius-sm); margin-bottom: 1rem; border: 1px solid transparent; }
    .pitb-security .alert-success { background: var(--pitb-success-bg); color: var(--pitb-success); border-color: #bbf7d0; }
    .pitb-security .alert-error { background: var(--pitb-danger-bg); color: var(--pitb-danger); border-color: #fecaca; }
    .pitb-security .errors { margin: 0 0 1rem; padding: .75rem 1rem  .75rem 2rem; background: var(--pitb-danger-bg); color: var(--pitb-danger); border-radius: var(--pitb-radius-sm); border: 1px solid #fecaca; }

    .pitb-security pre.json,
    .pitb-security pre.payload { background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: var(--pitb-radius-sm); overflow: auto; font-size: .8125rem; line-height: 1.45; }

    .pitb-security label { display: block; font-weight: 600; margin: 0 0 .4rem; color: #334155; }
    .pitb-security .field { margin-bottom: 1.1rem; }
    .pitb-security .field-hint { margin: .35rem 0 0; font-size: .8125rem; color: var(--pitb-muted); font-weight: 400; }
    .pitb-security input[type="email"],
    .pitb-security input[type="password"],
    .pitb-security input[type="text"],
    .pitb-security input[type="tel"],
    .pitb-security input[type="date"],
    .pitb-security input[type="number"],
    .pitb-security textarea,
    .pitb-security select {
        width: 100%;
        padding: .65rem .8rem;
        border: 1px solid var(--pitb-border-strong);
        border-radius: var(--pitb-radius-sm);
        font: inherit;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pitb-security input:focus,
    .pitb-security textarea:focus,
    .pitb-security select:focus {
        outline: none;
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
    }

    .pitb-security .form-card { padding: 0; overflow: hidden; }
    .pitb-security .form-section { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--pitb-border); }
    .pitb-security .form-section:last-child { border-bottom: 0; }
    .pitb-security .form-section__title { margin: 0 0 .25rem; font-size: 1rem; font-weight: 700; }
    .pitb-security .form-section__desc { margin: 0 0 1rem; color: var(--pitb-muted); font-size: .875rem; }
    .pitb-security .form-actions {
        display: flex; flex-wrap: wrap; gap: .65rem; align-items: center;
        padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--pitb-border);
    }

    .pitb-security .choice-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: .65rem; }
    .pitb-security .choice-item {
        display: flex; align-items: center; gap: .55rem;
        padding: .7rem .8rem; border: 1px solid var(--pitb-border-strong); border-radius: var(--pitb-radius-sm);
        background: #fff; cursor: pointer; font-weight: 500; transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .pitb-security .choice-item:hover { border-color: #93c5fd; background: #f8fbff; }
    .pitb-security .choice-item:has(input:checked) { border-color: #3b82f6; background: var(--pitb-primary-soft); box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .15); }
    .pitb-security .choice-item input { width: auto; margin: 0; accent-color: var(--pitb-primary); }

    .pitb-security .toggle-list { display: grid; gap: .75rem; }
    .pitb-security .toggle-item {
        display: flex; align-items: flex-start; gap: .75rem;
        padding: .85rem 1rem; border: 1px solid var(--pitb-border); border-radius: var(--pitb-radius-sm); background: #f8fafc;
    }
    .pitb-security .toggle-item input { width: auto; margin-top: .2rem; accent-color: var(--pitb-primary); }
    .pitb-security .toggle-item__label { font-weight: 600; margin: 0; }
    .pitb-security .toggle-item__hint { margin: .2rem 0 0; color: var(--pitb-muted); font-size: .8125rem; font-weight: 400; }

    .pitb-security .detail-grid { display: grid; gap: .75rem; }
    .pitb-security .detail-row { display: grid; grid-template-columns: 10rem 1fr; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid var(--pitb-border); }
    .pitb-security .detail-row:last-child { border-bottom: 0; }
    .pitb-security .detail-row dt { margin: 0; font-weight: 600; color: #475569; }
    .pitb-security .detail-row dd { margin: 0; }

    .pitb-security .empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--pitb-muted); }

    .pitb-security .captcha-wrap { margin: .25rem 0 .5rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .pitb-security .captcha-image { display: block; max-width: 100%; height: auto; border-radius: 4px; }
    .pitb-security .captcha-refresh { white-space: nowrap; }
    .pitb-security .auth-links { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid var(--pitb-border); font-size: .875rem; }
    .pitb-security .auth-links a { color: var(--pitb-primary); text-decoration: none; font-weight: 600; }
    .pitb-security .auth-links a:hover { text-decoration: underline; }

    .pitb-security .pitb-password-strength { margin: .5rem 0 0; }
    .pitb-security .pitb-password-strength[hidden] { display: none !important; }
    .pitb-security .pitb-password-strength__bar { height: .35rem; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .pitb-security .pitb-password-strength__fill { height: 100%; width: 0; border-radius: inherit; transition: width .2s ease, background-color .2s ease; background: #ef4444; }
    .pitb-security .pitb-password-strength__fill[data-strength="fair"] { background: #f59e0b; }
    .pitb-security .pitb-password-strength__fill[data-strength="good"] { background: #3b82f6; }
    .pitb-security .pitb-password-strength__fill[data-strength="strong"] { background: #16a34a; }
    .pitb-security .pitb-password-strength__label { margin: .5rem 0 .25rem; font-size: .8125rem; }
    .pitb-security .pitb-password-strength__rules { list-style: none; margin: 0; padding: 0; font-size: .8125rem; }
    .pitb-security .pitb-password-strength__rules li { padding: .15rem 0 .15rem 1.25rem; position: relative; color: #64748b; }
    .pitb-security .pitb-password-strength__rules li::before { content: '○'; position: absolute; left: 0; }
    .pitb-security .pitb-password-strength__rules li.is-passed { color: #166534; }
    .pitb-security .pitb-password-strength__rules li.is-passed::before { content: '✓'; }
    .pitb-security .pitb-password-strength__rules li.is-failed::before { content: '○'; color: #94a3b8; }
    .pitb-security .pitb-password-strength__status { margin: .5rem 0 0; font-size: .8125rem; font-weight: 600; }
    .pitb-security .pitb-password-strength__status.is-valid { color: #166534; }
    .pitb-security .pitb-password-strength__status.is-invalid { color: #b45309; }

    .pitb-security .pagination { margin-top: 1rem; }

    @media (max-width: 900px) {
        .pitb-security .admin-app { grid-template-columns: 1fr; }
        .pitb-security .admin-sidebar { border-right: 0; border-bottom: 1px solid rgba(255, 255, 255, .08); }
        .pitb-security .admin-nav { flex-direction: row; flex-wrap: wrap; }
        .pitb-security .admin-nav a { flex: 1 1 auto; }
        .pitb-security .admin-main { padding: 1rem; }
        .pitb-security .detail-row { grid-template-columns: 1fr; gap: .25rem; }
    }
</style>
