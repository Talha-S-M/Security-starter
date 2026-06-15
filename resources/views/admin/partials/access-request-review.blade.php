<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div class="card">
        <h2>Access request #{{ $accessRequest->id }}</h2>

        <p><strong>Status:</strong> {{ $accessRequest->status }}</p>
        <p><strong>Type:</strong> {{ str_replace('_', ' ', $accessRequest->type) }}</p>
        <p><strong>Target:</strong> {{ $accessRequest->target_type }} #{{ $accessRequest->target_id }}</p>
        <p><strong>Requester ID:</strong> {{ $accessRequest->requester_id }}</p>

        @if ($accessRequest->justification)
            <p><strong>Justification:</strong> {{ $accessRequest->justification }}</p>
        @endif

        <pre class="payload">{{ json_encode($accessRequest->payload, JSON_PRETTY_PRINT) }}</pre>

        @if ($accessRequest->review_notes)
            <p><strong>Review notes:</strong> {{ $accessRequest->review_notes }}</p>
        @endif

        @if ($canApprove)
            <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.approve'), $accessRequest) }}" style="margin-top: 1rem;">
                @csrf
                <label for="approve_notes">Approval notes (optional)</label>
                <textarea id="approve_notes" name="review_notes" rows="3"></textarea>
                <button class="btn btn-primary" type="submit">Approve</button>
            </form>

            <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.reject'), $accessRequest) }}" style="margin-top: 1rem;">
                @csrf
                <label for="reject_notes">Rejection reason (optional)</label>
                <textarea id="reject_notes" name="review_notes" rows="3"></textarea>
                <button class="btn btn-secondary" type="submit">Reject</button>
            </form>
        @endif

        <div style="margin-top: .75rem;">
            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Back</a>
        </div>
    </div>
</div>
