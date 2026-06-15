<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form class="filters" method="GET" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events')) }}">
        <input type="text" name="event_type" placeholder="Event type" value="{{ $filters['event_type'] ?? '' }}">
        <input type="number" name="user_id" placeholder="User ID" value="{{ $filters['user_id'] ?? '' }}">
        <select name="success">
            <option value="">All results</option>
            <option value="1" @selected(($filters['success'] ?? '') === '1')>Success</option>
            <option value="0" @selected(($filters['success'] ?? '') === '0')>Failed</option>
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Event</th>
                    <th>User</th>
                    <th>IP</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td>{{ $event->created_at }}</td>
                        <td>
                            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.security-events.show'), $event) }}">
                                {{ $event->event_type }}
                            </a>
                        </td>
                        <td>{{ $event->user_id ?? '—' }}</td>
                        <td>{{ $event->ip_address ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $event->success ? 'badge-success' : 'badge-danger' }}">
                                {{ $event->success ? 'Success' : 'Failed' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No security events found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div>{{ $events->links() }}</div>
    </div>
</div>
