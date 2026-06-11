<?php

namespace Pitbphp\Security\Auditing;

use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Contracts\AuditLoggerInterface;

class NullAuditLogger implements AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        // Intentionally no-op; security_events table handles auth events.
    }

    public function prune(\DateTimeInterface $before): int
    {
        return 0;
    }
}
