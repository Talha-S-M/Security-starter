<div class="pitb-security">
    @include('security::admin.partials.styles')

    <p class="muted">Source: <strong>{{ $driverLabel }}</strong></p>

    <form class="filters" method="GET" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">
        <input type="text" name="search" placeholder="Search" value="{{ $filters['search'] ?? '' }}">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <div class="card">
        @if (! $logs)
            <p class="muted">Audit trail not available for the current auditing driver.</p>
        @else
            <table>
                <thead>
                    <tr>
                        @if (config('security.auditing.driver') === 'auditing')
                            <th>ID</th><th>Time</th><th>Event</th><th>Model</th><th>User</th>
                        @else
                            <th>ID</th><th>Time</th><th>Log</th><th>Description</th><th>Causer</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $row)
                        <tr>
                            @if (config('security.auditing.driver') === 'auditing')
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->created_at }}</td>
                                <td>{{ $row->event }}</td>
                                <td>{{ class_basename($row->auditable_type ?? '') }} #{{ $row->auditable_id ?? '' }}</td>
                                <td>{{ $row->user_id ?? '—' }}</td>
                            @else
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->created_at }}</td>
                                <td>{{ $row->log_name }}</td>
                                <td>{{ $row->description }}</td>
                                <td>{{ $row->causer_id ?? '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No audit records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div>{{ $logs->links() }}</div>
        @endif
    </div>
</div>
