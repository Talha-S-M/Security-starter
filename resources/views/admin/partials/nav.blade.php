{{-- Horizontal nav for embedding inside an existing app layout. --}}
<div class="pitb-security">
    @include('security::admin.partials.styles')

    <nav class="toolbar" style="margin-bottom: 1rem;">
        <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.summary')) }}">Dashboard</a>
        @can('users.view')
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Users</a>
        @endcan
        @can('roles.view')
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Roles</a>
        @endcan
        @can('permissions.view')
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.permissions')) }}">Permissions</a>
        @endcan
        @can('audit-logs.view')
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Security events</a>
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">Audit trail</a>
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.reviews')) }}">Reviews</a>
        @endcan
        @canany(['access-requests.view', 'access-requests.approve'])
            <a class="btn btn-secondary btn-sm" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Access requests</a>
        @endcanany
    </nav>
</div>
