<?php

namespace Pitbphp\Security\Support;

use Illuminate\Foundation\Application;

class AuditingPackageResolver
{
    public static function driverPackage(string $driver): ?string
    {
        return match ($driver) {
            'activitylog' => 'spatie/laravel-activitylog:'.self::activityLogConstraint(),
            'auditing' => 'owen-it/laravel-auditing:'.self::auditingConstraint(),
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

    public static function activityLogConstraint(): string
    {
        return '^4.1';
    }

    public static function auditingConstraint(): string
    {
        return '^13.0';
    }

    protected static function laravelVersion(): int
    {
        if (defined(Application::class.'::VERSION')) {
            return (int) Application::VERSION;
        }

        return 11;
    }
}
