@include('security::admin.partials.page-open', [
    'title' => 'Security event',
    'subtitle' => $event->event_type,
])

<div class="card panel">
    <h2 class="panel__title">Event details</h2>
    <dl class="detail-grid">
        <div class="detail-row"><dt>Actor</dt><dd>{{ \Pitbphp\Security\Support\SecurityActorPresenter::causerForEvent($event) }}</dd></div>
        <div class="detail-row"><dt>Subject</dt><dd>{{ \Pitbphp\Security\Support\SecurityActorPresenter::subjectForEvent($event) }}</dd></div>
        <div class="detail-row"><dt>Time</dt><dd>{{ $event->created_at }}</dd></div>
        <div class="detail-row"><dt>Event</dt><dd>{{ $event->event_type }}</dd></div>
        <div class="detail-row"><dt>IP address</dt><dd>{{ $event->ip_address ?? '—' }}</dd></div>
        <div class="detail-row"><dt>User agent</dt><dd>{{ $event->user_agent ?? '—' }}</dd></div>
        <div class="detail-row">
            <dt>Result</dt>
            <dd>
                <span class="badge {{ $event->success ? 'badge-success' : 'badge-danger' }}">
                    {{ $event->success ? 'Success' : 'Failed' }}
                </span>
            </dd>
        </div>
    </dl>
</div>

<div class="card panel">
    <h2 class="panel__title">Properties</h2>
    <pre class="json">{{ json_encode($event->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</div>

<div class="toolbar" style="margin-top:1rem;">
    <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">Back to events</a>
</div>

@include('security::admin.partials.page-close')
