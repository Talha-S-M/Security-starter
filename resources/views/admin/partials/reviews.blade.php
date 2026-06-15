<div class="pitb-security">
    @include('security::admin.partials.styles')

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Performed by</th>
                    <th>Performed at</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td>{{ $review->id }}</td>
                        <td>{{ $review->type }}</td>
                        <td>{{ $review->performed_by }}</td>
                        <td>{{ $review->performed_at }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($review->notes, 80) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No reviews recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div>{{ $reviews->links() }}</div>
    </div>
</div>
