<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Support\SecurityRoutes;

class AccessRequestController extends Controller
{
    public function index(Request $request, AccessProvisioningService $provisioning): View
    {
        $query = AccessRequest::query()->latest();

        if ($provisioning->canApprove($request->user())) {
            $query->where('status', AccessRequest::STATUS_PENDING);
        } else {
            $query->where('requester_id', $request->user()->getAuthIdentifier());
        }

        return view('security::admin.partials.access-requests', [
            'requests' => $query->paginate((int) config('security.admin.per_page', 25)),
            'canApprove' => $provisioning->canApprove($request->user()),
        ]);
    }

    public function show(Request $request, AccessRequest $accessRequest, AccessProvisioningService $provisioning): View
    {
        if (! $provisioning->canApprove($request->user())
            && (int) $accessRequest->requester_id !== (int) $request->user()->getAuthIdentifier()) {
            abort(403);
        }

        return view('security::admin.partials.access-request-review', [
            'accessRequest' => $accessRequest,
            'canApprove' => $provisioning->canApprove($request->user()) && $accessRequest->isPending(),
        ]);
    }

    public function approve(Request $request, AccessRequest $accessRequest, AccessProvisioningService $provisioning): RedirectResponse
    {
        if (! $provisioning->canApprove($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $provisioning->approve($accessRequest, $request->user(), $validated['review_notes'] ?? null);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.access-requests'))
            ->with('status', 'Access request approved and applied.');
    }

    public function reject(Request $request, AccessRequest $accessRequest, AccessProvisioningService $provisioning): RedirectResponse
    {
        if (! $provisioning->canApprove($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $provisioning->reject($accessRequest, $request->user(), $validated['review_notes'] ?? null);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.access-requests'))
            ->with('status', 'Access request rejected.');
    }
}
