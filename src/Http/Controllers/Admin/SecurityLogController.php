<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Services\AuditLogReader;

class SecurityLogController extends Controller
{
    public function securityEvents(Request $request, AuditLogReader $reader): View
    {
        return view('security::admin.partials.security-events', [
            'events' => $reader->securityEvents($request->only(['event_type', 'success', 'user_id', 'from', 'to'])),
            'filters' => $request->only(['event_type', 'success', 'user_id', 'from', 'to']),
        ]);
    }

    public function showSecurityEvent(int $event, AuditLogReader $reader): View
    {
        $record = $reader->securityEvent($event);

        abort_if(! $record, 404);

        return view('security::admin.partials.security-event-detail', [
            'event' => $record,
        ]);
    }

    public function auditTrail(Request $request, AuditLogReader $reader): View
    {
        return view('security::admin.partials.audit-trail', [
            'logs' => $reader->auditTrail($request->only(['search'])),
            'driverLabel' => $reader->auditDriverLabel(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function reviews(AuditLogReader $reader): View
    {
        return view('security::admin.partials.reviews', [
            'reviews' => $reader->reviews(),
        ]);
    }
}
