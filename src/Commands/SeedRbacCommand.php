<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Services\RbacSeederService;

class SeedRbacCommand extends Command
{
    protected $signature = 'security:seed-rbac';

    protected $description = 'Seed default PITB roles and permissions';

    public function handle(RbacSeederService $seeder): int
    {
        if (! config('security.permissions.enabled', true)) {
            $this->warn('RBAC is disabled in config/security.php.');

            return self::FAILURE;
        }

        if (! $seeder->isAvailable()) {
            $this->error('spatie/laravel-permission is not available. Run php artisan security:install first.');

            return self::FAILURE;
        }

        $created = $seeder->seed();

        $this->info("Seeded {$created['permissions']} permission(s) and {$created['roles']} role(s).");

        return self::SUCCESS;
    }
}
