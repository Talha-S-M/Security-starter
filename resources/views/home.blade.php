<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITB Security</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
            <h1>PITB Security Dashboard</h1>
            <p class="muted">Use quick links below to test and verify create/edit/update flows.</p>

            <div class="pitb-admin-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-top:1rem;">
                @auth
                    @can('users.view')
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Users</a>
                    @endcan
                    @can('users.create')
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.create')) }}">Create User</a>
                    @endcan
                    @can('roles.view')
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Roles</a>
                    @endcan
                    @can('permissions.view')
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
                    @endcan
                    @can('audit-logs.view')
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Security Events</a>
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">Audit Trail</a>
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.reviews')) }}">Reviews</a>
                    @endcan
                    @canany(['access-requests.view', 'access-requests.approve'])
                        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Access Requests</a>
                    @endcanany
                    <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.summary')) }}">Summary</a>
                @endauth

                @guest
                    <a class="btn btn-secondary" href="{{ route('login') }}">Login</a>
                    @if (config('security.auth.register', false))
                        <a class="btn btn-secondary" href="{{ route('register') }}">Request Account</a>
                    @endif
                    <a class="btn btn-secondary" href="{{ route('password.request') }}">Forgot Password</a>
                @endguest
            </div>
        </div>
    </main>
</body>
</html>
