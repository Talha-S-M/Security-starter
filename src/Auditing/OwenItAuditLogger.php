<?php

namespace Pitbphp\Security\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Contracts\AuditLoggerInterface;

class OwenItAuditLogger implements AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        // Owen-It handles model CRUD via the Auditable trait automatically.
        // Auth and review events are stored in security_events by the package.
    }

    public function prune(\DateTimeInterface $before): int
    {
        if (! class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            return 0;
        }

        return DB::table(config('audit.drivers.database.table', 'audits'))
            ->where('created_at', '<', $before)
            ->delete();
    }
}
