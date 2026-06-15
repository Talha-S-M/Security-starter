<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Services\AuditLogReader;

class DashboardController extends Controller
{
    public function summary(): View
    {
        return view('security::admin.partials.dashboard-summary', [
            'driverLabel' => app(AuditLogReader::class)->auditDriverLabel(),
            'recentCount' => SecurityEvent::query()->count(),
        ]);
    }
}
