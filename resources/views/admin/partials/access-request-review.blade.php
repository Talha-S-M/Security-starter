@include('security::admin.partials.page-open', [
    'title' => 'Access request #'.$accessRequest->id,
    'subtitle' => str_replace('_', ' ', $accessRequest->type),
])

<div class="card panel">
    <h2 class="panel__title">Request details</h2>
    <dl class="detail-grid">
        <div class="detail-row"><dt>Status</dt><dd><span class="badge badge-neutral">{{ $accessRequest->status }}</span></dd></div>
        <div class="detail-row"><dt>Type</dt><dd>{{ str_replace('_', ' ', $accessRequest->type) }}</dd></div>
        <div class="detail-row"><dt>Target</dt><dd>{{ $accessRequest->target_type }} #{{ $accessRequest->target_id }}</dd></div>
        <div class="detail-row"><dt>Requester ID</dt><dd>{{ $accessRequest->requester_id }}</dd></div>
        @if ($accessRequest->justification)
            <div class="detail-row"><dt>Justification</dt><dd>{{ $accessRequest->justification }}</dd></div>
        @endif
        @if ($accessRequest->review_notes)
            <div class="detail-row"><dt>Review notes</dt><dd>{{ $accessRequest->review_notes }}</dd></div>
        @endif
    </dl>
</div>

<div class="card panel">
    <h2 class="panel__title">Payload</h2>
    <pre class="payload">{{ json_encode($accessRequest->payload, JSON_PRETTY_PRINT) }}</pre>
</div>

@if ($canApprove)
    <div class="card form-card" style="margin-top:1rem;">
        <section class="form-section">
            <h2 class="form-section__title">Approve request</h2>
            <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.approve'), $accessRequest) }}">
                @csrf
                <div class="field">
                    <label for="approve_notes">Approval notes (optional)</label>
                    <textarea id="approve_notes" name="review_notes" rows="3"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Approve</button>
            </form>
        </section>

        <section class="form-section">
            <h2 class="form-section__title">Reject request</h2>
            <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests.reject'), $accessRequest) }}">
                @csrf
                <div class="field">
                    <label for="reject_notes">Rejection reason (optional)</label>
                    <textarea id="reject_notes" name="review_notes" rows="3"></textarea>
                </div>
                <button class="btn btn-danger" type="submit">Reject</button>
            </form>
        </section>
    </div>
@endif

<div class="toolbar" style="margin-top:1rem;">
    <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.access-requests')) }}">Back to requests</a>
</div>

@include('security::admin.partials.page-close')
