<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITB Security</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    <div class="admin-app">
        @include('security::admin.partials.sidebar-nav')

        <main class="admin-main">
            <header class="page-header">
                <h1>Security dashboard</h1>
                <p class="page-subtitle">Quick access to users, roles, permissions, logs, and reviews.</p>
            </header>

            <div class="page-body">
                <div class="stat-grid">
                    @auth
                        @can('users.view')
                            <a class="stat-card" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}" style="text-decoration:none;color:inherit;">
                                <div class="stat-card__label">Users</div>
                                <div class="stat-card__value" style="font-size:1rem;">Manage accounts</div>
                            </a>
                        @endcan
                        @can('roles.view')
                            <a class="stat-card" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}" style="text-decoration:none;color:inherit;">
                                <div class="stat-card__label">Roles</div>
                                <div class="stat-card__value" style="font-size:1rem;">Edit role access</div>
                            </a>
                        @endcan
                        @can('audit-logs.view')
                            <a class="stat-card" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}" style="text-decoration:none;color:inherit;">
                                <div class="stat-card__label">Security events</div>
                                <div class="stat-card__value" style="font-size:1rem;">Auth &amp; access</div>
                            </a>
                        @endcan
                    @else
                        <div class="stat-card">
                            <div class="stat-card__label">Welcome</div>
                            <div class="stat-card__value" style="font-size:1rem;">Sign in to continue</div>
                        </div>
                    @endauth
                </div>

                <div class="card">
                    <h2 class="panel__title">Quick actions</h2>
                    <div class="toolbar">
                        @auth
                            @can('users.create')
                                <a class="btn btn-primary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.create')) }}">Create user</a>
                            @endcan
                            @can('permissions.view')
                                <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
                            @endcan
                            @can('audit-logs.view')
                                <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">Audit trail</a>
                                <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.reviews')) }}">Reviews</a>
                            @endcan
                            @canany(['access-requests.view', 'access-requests.approve'])
                                <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Access requests</a>
                            @endcanany
                            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.summary')) }}">Summary</a>
                        @else
                            <a class="btn btn-primary" href="{{ route('login') }}">Login</a>
                            @if (\Pitbphp\Security\Support\SecurityTier::registrationEnabled())
                                <a class="btn btn-secondary" href="{{ route('register') }}">Create account</a>
                            @endif
                            <a class="btn btn-secondary" href="{{ route('password.request') }}">Forgot password</a>
                        @endauth
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
