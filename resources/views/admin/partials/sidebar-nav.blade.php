@php
    use Pitbphp\Security\Support\SecurityRoutes;

    $route = fn (string $name) => SecurityRoutes::adminName('partials.'.$name);
    $active = fn (string $pattern) => request()->routeIs(SecurityRoutes::adminName('partials.'.$pattern));
@endphp

<aside class="admin-sidebar">
    <div class="admin-brand">
        <p class="admin-brand__title">PITB Security</p>
        <p class="admin-brand__subtitle">Administration</p>
    </div>

    <nav class="admin-nav">
        <span class="admin-nav__section">Overview</span>
        <a href="{{ route(SecurityRoutes::name('home')) }}" class="{{ $active('summary') ? 'is-active' : '' }}">Dashboard</a>

        @can('users.view')
            <span class="admin-nav__section">Access control</span>
            <a href="{{ route($route('users')) }}" class="{{ $active('users*') ? 'is-active' : '' }}">Users</a>
        @endcan
        @can('roles.view')
            <a href="{{ route($route('roles')) }}" class="{{ $active('roles*') ? 'is-active' : '' }}">Roles</a>
        @endcan
        @can('permissions.view')
            <a href="{{ route($route('permissions')) }}" class="{{ $active('permissions') ? 'is-active' : '' }}">Permissions</a>
        @endcan
        @canany(['access-requests.view', 'access-requests.approve'])
            <a href="{{ route($route('access-requests')) }}" class="{{ $active('access-requests*') ? 'is-active' : '' }}">Access requests</a>
        @endcanany

        @can('audit-logs.view')
            <span class="admin-nav__section">Monitoring</span>
            <a href="{{ route($route('security-events')) }}" class="{{ $active('security-events*') ? 'is-active' : '' }}">Security events</a>
            <a href="{{ route($route('audit-trail')) }}" class="{{ $active('audit-trail') ? 'is-active' : '' }}">Audit trail</a>
            <a href="{{ route($route('reviews')) }}" class="{{ $active('reviews') ? 'is-active' : '' }}">Reviews</a>
        @endcan
    </nav>

    <div class="admin-sidebar__footer">
        <a class="btn btn-secondary btn-sm" href="{{ route($route('summary')) }}">Security summary</a>
        @auth
            <form method="POST" action="{{ route('logout') }}" style="margin-top: .5rem;">
                @csrf
                <button class="btn btn-secondary btn-sm" type="submit">Logout</button>
            </form>
        @endauth
    </div>
</aside>
