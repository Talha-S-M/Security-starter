<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Services\AuditLogReader;

class DashboardController extends Controller
{
    public function summary(): View
    {
        $pendingApprovals = 0;
        $user = auth()->user();

        if ($user && app(AccessProvisioningService::class)->canApprove($user)) {
            $pendingApprovals = AccessRequest::query()
                ->where('status', AccessRequest::STATUS_PENDING)
                ->count();
        }

        return view('security::admin.partials.dashboard-summary', [
            'driverLabel' => app(AuditLogReader::class)->auditDriverLabel(),
            'recentCount' => SecurityEvent::query()->count(),
            'pendingApprovals' => $pendingApprovals,
        ]);
    }
}
