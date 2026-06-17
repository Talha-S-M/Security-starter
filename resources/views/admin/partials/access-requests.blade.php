@include('security::admin.partials.page-open', [
    'title' => $canApprove ? 'Access requests' : 'My access requests',
    'subtitle' => $canApprove ? 'Review and approve pending provisioning changes' : 'Track submitted access change requests',
])

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ str_replace('_', ' ', $item->type) }}</td>
                        <td>{{ $item->target_type }} #{{ $item->target_id }}</td>
                        <td><span class="badge badge-neutral">{{ $item->status }}</span></td>
                        <td>{{ $item->created_at?->diffForHumans() }}</td>
                        <td>
                            <a class="table-link" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.show'), $item) }}">
                                {{ $canApprove && $item->isPending() ? 'Review' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">No access requests.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $requests->links() }}</div>
</div>

@include('security::admin.partials.page-close')
