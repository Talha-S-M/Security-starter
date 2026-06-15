<div class="pitb-security">
    @include('security::admin.partials.styles')

    <div class="card">
        <table>
            <tr><th>Type</th><td>{{ $event->event_type }}</td></tr>
            <tr><th>Time</th><td>{{ $event->created_at }}</td></tr>
            <tr><th>User ID</th><td>{{ $event->user_id ?? '—' }}</td></tr>
            <tr><th>IP</th><td>{{ $event->ip_address ?? '—' }}</td></tr>
            <tr><th>User agent</th><td>{{ $event->user_agent ?? '—' }}</td></tr>
            <tr>
                <th>Result</th>
                <td>
                    <span class="badge {{ $event->success ? 'badge-success' : 'badge-danger' }}">
                        {{ $event->success ? 'Success' : 'Failed' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="card" style="margin-top: .75rem;">
        <strong>Properties</strong>
        <pre class="json">{{ json_encode($event->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
