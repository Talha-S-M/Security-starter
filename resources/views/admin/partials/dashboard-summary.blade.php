<div class="pitb-security">
    @include('security::admin.partials.styles')

    <div class="card">
        <p class="muted">Audit driver: <strong>{{ $driverLabel }}</strong></p>

        @can('audit-logs.view')
            <p>Recent security events: <strong>{{ $recentCount }}</strong></p>
        @endcan

        @if (($pendingApprovals ?? 0) > 0)
            <p>
                Pending access approvals: <strong>{{ $pendingApprovals }}</strong>
                — <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Review now</a>
            </p>
        @endif
    </div>
</div>
