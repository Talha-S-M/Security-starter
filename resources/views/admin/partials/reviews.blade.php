@include('security::admin.partials.page-open', ['title' => 'Manual reviews', 'subtitle' => 'Recorded access and log review activity'])

<div class="card">
    <div class="table-wrap">
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
                        <td>{{ $review->performer_label ?? ('User #'.$review->performed_by) }}</td>
                        <td>{{ $review->performed_at }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($review->notes, 80) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">No reviews recorded yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $reviews->links() }}</div>
</div>

@include('security::admin.partials.page-close')
