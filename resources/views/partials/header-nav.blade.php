<nav>
    <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('home')) }}">Security Home</a>

    @guest
        <a href="{{ route('login') }}">Login</a>
        @if (config('security.auth.register', false))
            <a href="{{ route('register') }}">Request account</a>
        @endif
    @endguest

    @auth
        @can('audit-logs.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.summary')) }}">Summary</a>
        @endcan
        @can('users.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Users</a>
        @endcan
        @can('users.create')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.create')) }}">Create user</a>
        @endcan
        @can('roles.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Roles</a>
        @endcan
        @can('permissions.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
        @endcan
        @can('audit-logs.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Security events</a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">Audit trail</a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.reviews')) }}">Reviews</a>
        @endcan
        @canany(['access-requests.view', 'access-requests.approve'])
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Access requests</a>
        @endcan

        <form method="POST" action="{{ route('logout') }}" style="display:inline-block; margin: 0;">
            @csrf
            <button class="btn btn-secondary" type="submit">Logout</button>
        </form>
    @endauth
</nav>
