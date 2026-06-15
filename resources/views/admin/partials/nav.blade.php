<div class="pitb-security">
    @include('security::admin.partials.styles')

    <nav>
        @can('audit-logs.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Security events</a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">Audit trail</a>
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.reviews')) }}">Reviews</a>
        @endcan
        @can('users.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Users</a>
        @endcan
        @can('roles.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Roles</a>
        @endcan
        @can('permissions.view')
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
        @endcan
        @canany(['access-requests.view', 'access-requests.approve'])
            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Access requests</a>
        @endcanany
    </nav>
</div>
