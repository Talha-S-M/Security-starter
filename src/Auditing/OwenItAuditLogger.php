<?php

namespace Pitbphp\Security\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Support\SensitiveDataRedactor;

class OwenItAuditLogger implements AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        if (! class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            throw new \RuntimeException('Owen-It Auditing is not installed but SECURITY_AUDIT_DRIVER=auditing.');
        }

        if (! $subject && ! $causer) {
            return;
        }

        $request = request();
        $auditable = $subject ?? $causer;

        \OwenIt\Auditing\Models\Audit::query()->create([
            'user_type' => $causer ? $causer::class : null,
            'user_id' => $causer?->getKey(),
            'event' => $event,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => [],
            'new_values' => $this->sanitize($properties),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
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

    protected function sanitize(array $properties): array
    {
        return SensitiveDataRedactor::redact($properties);
    }
}
