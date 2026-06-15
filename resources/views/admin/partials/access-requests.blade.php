<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    <div class="card">
        <h2>{{ $canApprove ? 'Pending access requests' : 'My access requests' }}</h2>

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
                        <td>{{ $item->status }}</td>
                        <td>{{ $item->created_at?->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.show'), $item) }}">
                                {{ $canApprove && $item->isPending() ? 'Review' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No access requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $requests->links() }}
    </div>
</div>
