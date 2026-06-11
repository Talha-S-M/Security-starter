<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Services\SecurityEventLogger;

class PruneSecurityLogsCommand extends Command
{
    protected $signature = 'security:prune-logs
                            {--months= : Override retention months}';

    protected $description = 'Prune audit logs older than the configured retention period';

    public function handle(SecurityEventLogger $eventLogger, AuditLoggerInterface $auditLogger): int
    {
        $months = (int) ($this->option('months') ?: config('security.logging.retention_months', 12));
        $before = now()->subMonths($months);

        $events = $eventLogger->pruneEvents($before);
        $audits = $auditLogger->prune($before);

        $this->info("Pruned {$events} security event(s) and {$audits} audit log record(s) older than {$months} month(s).");

        return self::SUCCESS;
    }
}
