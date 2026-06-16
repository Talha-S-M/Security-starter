<?php

namespace Pitbphp\Security\Support;

class AuditingPackageResolver
{
    public static function driverPackage(string $driver): ?string
    {
        return match ($driver) {
            'activitylog' => 'spatie/laravel-activitylog',
            'auditing' => 'owen-it/laravel-auditing',
            default => null,
        };
    }

    public static function isPackageAvailable(string $driver): bool
    {
        return match ($driver) {
            'activitylog' => class_exists(\Spatie\Activitylog\Models\Activity::class),
            'auditing' => class_exists(\OwenIt\Auditing\Models\Audit::class),
            default => true,
        };
    }
}
