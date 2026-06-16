<nav>
    <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('home')) }}">Security Home</a>

    @guest
        <a href="{{ route('login') }}">Login</a>
        @if (config('security.auth.register', true))
            <a href="{{ route('register') }}">Register</a>
        @endif
    @endguest

    @auth
        @can('users.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Users</a>
        @endcan
        @can('roles.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Roles</a>
        @endcan
        @can('permissions.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
        @endcan
        @can('audit-logs.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Activity log</a>
        @endcan

        <form method="POST" action="{{ route('logout') }}" style="display:inline-block; margin: 0;">
            @csrf
            <button class="btn btn-secondary" type="submit">Logout</button>
        </form>
    @endauth
</nav>
