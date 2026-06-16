<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Services\SecurityEventLogger;

class PruneSecurityLogsCommand extends Command
{
    protected $signature = 'security:prune-logs
                            {--months= : Override retention months}';

    protected $description = 'Prune audit logs older than the configured retention period';

    public function handle(SecurityEventLogger $eventLogger, AuditLoggerInterface $auditLogger): int
    {
        $months = (int) ($this->option('months') ?: config('security.logging.retention_months', 12));
        $eventsBefore = now()->subMonths((int) config('security.logging.retention.security_events_months', $months));
        $auditsBefore = now()->subMonths((int) config('security.logging.retention.audit_trail_months', $months));
        $reviewsBefore = now()->subMonths((int) config('security.logging.retention.security_reviews_months', $months));
        $requestsBefore = now()->subMonths((int) config('security.logging.retention.access_requests_months', $months));

        $events = $eventLogger->pruneEvents($eventsBefore);
        $audits = $auditLogger->prune($auditsBefore);
        $reviews = SecurityReview::query()->where('performed_at', '<', $reviewsBefore)->delete();
        $requests = AccessRequest::query()->where('created_at', '<', $requestsBefore)->delete();

        $this->info("Pruned events={$events}, audits={$audits}, reviews={$reviews}, access_requests={$requests}.");

        return self::SUCCESS;
    }
}
