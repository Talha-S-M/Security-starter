<?php

namespace Pitbphp\Security\Contracts;

use Illuminate\Database\Eloquent\Model;

interface AuditLoggerInterface
{
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void;

    public function prune(\DateTimeInterface $before): int;
}
