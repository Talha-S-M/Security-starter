<?php

namespace Pitbphp\Security\Support;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Services\SecurityEventLogger;

final class SecurityLog
{
    public static function auth(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        static::logger()->auth($event, $success, $user, $extra);
    }

    public static function authorization(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        static::logger()->authorization($event, $success, $user, $extra);
    }

    public static function rbac(
        string $event,
        bool $success,
        Authenticatable|Model|null $subject = null,
        ?Authenticatable $causer = null,
        array $extra = []
    ): void {
        static::logger()->rbac($event, $success, $subject, $causer, $extra);
    }

    public static function recordReview(
        string $type,
        Authenticatable $performer,
        ?string $notes = null,
        array $metadata = []
    ): SecurityReview {
        return static::logger()->recordReview($type, $performer, $notes, $metadata);
    }

    public static function pruneEvents(DateTimeInterface $before): int
    {
        return static::logger()->pruneEvents($before);
    }

    protected static function logger(): SecurityEventLogger
    {
        return app(SecurityEventLogger::class);
    }
}
