<?php

namespace Pitbphp\Security\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Support\SensitiveDataRedactor;

class ActivityLogAuditLogger implements AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        if (! function_exists('activity')) {
            throw new \RuntimeException('Spatie Activitylog is not installed but SECURITY_AUDIT_DRIVER=activitylog.');
        }

        $logger = activity('security');

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->withProperties($this->sanitize($properties))->log($event);
    }

    public function prune(\DateTimeInterface $before): int
    {
        if (! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return 0;
        }

        return DB::table(config('activitylog.table_name', 'activity_log'))
            ->where('created_at', '<', $before)
            ->delete();
    }

    protected function sanitize(array $properties): array
    {
        return SensitiveDataRedactor::redact($properties);
    }
}
