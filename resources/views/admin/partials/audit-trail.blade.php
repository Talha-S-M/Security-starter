@include('security::admin.partials.page-open', [
    'title' => 'Audit trail',
    'subtitle' => 'User and role changes, access provisioning, and '.$driverLabel.' records',
])

<form class="toolbar filters" method="GET" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.audit-trail')) }}">
    <input type="text" name="search" placeholder="Search audit records" value="{{ $filters['search'] ?? '' }}">
    <button class="btn btn-primary" type="submit">Search</button>
</form>
@php 
    $showId = auth()->user()->hasRole('super-admin');
@endphp
<div class="card">
    @if (! $logs)
        <div class="empty-state">Audit trail is not available for the current auditing driver.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        @if (config('security.auditing.driver') === 'auditing')
                            @if ($showId)<th>ID</th>@endif
                            <th>Time</th><th>Event</th><th>Model</th><th>Details</th><th>Actor</th>
                        @else
                        @if ($showId)<th>ID</th>@endif
                        <th>Time</th><th>Log</th><th>Description</th><th>Details</th><th>Actor</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $row)
                        <tr>
                            @if (config('security.auditing.driver') === 'auditing')
                            @if ($showId)<td>{{ $row->id }}</td>@endif
                                <td>{{ $row->created_at }}</td>
                                <td>{{ $row->event }}</td>
                                <td>{{ class_basename($row->auditable_type ?? '') }} #{{ $row->auditable_id ?? '' }}</td>
                                <td class="audit-detail">{{ $row->change_summary ?? '—' }}</td>
                                <td>{{ $row->causer_label ?? '—' }}</td>
                            @else
                            @if ($showId)<td>{{ $row->id }}</td>@endif
                                <td>{{ $row->created_at }}</td>
                                <td>{{ $row->log_name }}</td>
                                <td>{{ $row->description }}</td>
                                <td class="audit-detail">{{ $row->change_summary ?? '—' }}</td>
                                <td>{{ $row->causer_label ?? '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state">No audit records found.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $logs->links() }}</div>
    @endif
</div>

@include('security::admin.partials.page-close')
