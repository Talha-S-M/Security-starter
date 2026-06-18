<aside class="sidebar">
    <h1>PITB Security</h1>
    <nav>
        <a href="{{ route('/security') }}"
           class="{{ request()->routeIs('/security') ? 'active' : '' }}">
            Dashboard
        </a>

        @can('audit-logs.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('logs.security-events')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('logs.security-events*')) ? 'active' : '' }}">
                Security Events
            </a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('logs.audit-trail')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('logs.audit-trail')) ? 'active' : '' }}">
                Audit Trail
            </a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('reviews.index')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('reviews.index')) ? 'active' : '' }}">
                Manual Reviews
            </a>
        @endcan

        @can('users.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('users.index')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('users.*')) ? 'active' : '' }}">
                Users
            </a>
        @endcan

        @can('roles.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('roles.index')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('roles.*')) ? 'active' : '' }}">
                Roles
            </a>
        @endcan

        @can('permissions.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('permissions.index')) }}"
               class="{{ request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('permissions.index')) ? 'active' : '' }}">
                Permissions
            </a>
        @endcan
    </nav>
</aside>
