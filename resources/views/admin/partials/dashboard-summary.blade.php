@include('security::admin.partials.page-open', ['title' => 'Dashboard', 'subtitle' => 'Security overview and pending actions'])

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label">Audit driver</div>
        <div class="stat-card__value" style="font-size:1.1rem;">{{ $driverLabel }}</div>
    </div>

    @can('audit-logs.view')
        <div class="stat-card">
            <div class="stat-card__label">Recent security events</div>
            <div class="stat-card__value">{{ $recentCount }}</div>
        </div>
    @endcan

    @if (($pendingApprovals ?? 0) > 0)
        <div class="stat-card">
            <div class="stat-card__label">Pending access approvals</div>
            <div class="stat-card__value">{{ $pendingApprovals }}</div>
        </div>
    @endif
</div>

@if (($pendingApprovals ?? 0) > 0)
    <div class="card">
        <p class="muted" style="margin:0 0 .75rem;">Access changes are waiting for review.</p>
        <a class="btn btn-primary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Review access requests</a>
    </div>
@endif

@include('security::admin.partials.page-close')
