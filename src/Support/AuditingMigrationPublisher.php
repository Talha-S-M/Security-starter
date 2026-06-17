<?php

namespace Pitbphp\Security\Support;

use Illuminate\Console\Command;

class AuditingMigrationPublisher
{
    /**
     * Publish third-party auditing migration files.
     *
     * @return array<int, string>
     */
    public static function publish(Command $command, string $auditDriver, bool $force = false): array
    {
        $published = [];

        if ($auditDriver === 'activitylog' && class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $command->call('vendor:publish', [
                '--provider' => 'Spatie\Activitylog\ActivitylogServiceProvider',
                '--tag' => 'activitylog-migrations',
                '--force' => $force,
            ]);
            $published[] = 'activitylog';
            $command->info('  Published spatie/laravel-activitylog migrations.');
        }

        if ($auditDriver === 'auditing' && class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            $command->call('vendor:publish', [
                '--provider' => 'OwenIt\Auditing\AuditingServiceProvider',
                '--tag' => 'migrations',
                '--force' => $force,
            ]);
            $published[] = 'auditing';
            $command->info('  Published owen-it/laravel-auditing migrations.');
        }

        return $published;
    }

    public static function requiredTable(string $auditDriver): ?string
    {
        return match ($auditDriver) {
            'activitylog' => (string) config('activitylog.table_name', 'activity_log'),
            'auditing' => (string) config('audit.drivers.database.table', 'audits'),
            default => null,
        };
    }
}
