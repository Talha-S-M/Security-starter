<?php

namespace Pitbphp\Security\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Contracts\AuditLoggerInterface;

class ActivityLogAuditLogger implements AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        if (! function_exists('activity')) {
            return;
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
        $sensitive = ['password', 'otp', 'token', 'secret', 'captcha'];

        foreach ($properties as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $properties[$key] = '[REDACTED]';
            }
        }

        return $properties;
    }
}
