<?php

namespace Pitbphp\Security\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SanctumInstaller
{
    public const PACKAGE = 'laravel/sanctum';

    public static function isAvailable(): bool
    {
        return class_exists(\Laravel\Sanctum\Sanctum::class);
    }

    public static function userHasApiTokens(): bool
    {
        $model = config('security.user.model');

        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        return in_array(\Laravel\Sanctum\HasApiTokens::class, class_uses_recursive($model), true);
    }

    public static function publish(Command $command, bool $force = false): void
    {
        if (! self::isAvailable()) {
            return;
        }

        $command->info('Publishing Sanctum config and migrations...');

        $command->call('vendor:publish', [
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
            '--tag' => 'sanctum-config',
            '--force' => $force,
        ]);

        $command->call('vendor:publish', [
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
            '--tag' => 'sanctum-migrations',
            '--force' => $force,
        ]);
    }

    public static function personalAccessTokensTableExists(): bool
    {
        try {
            return Schema::hasTable('personal_access_tokens');
        } catch (\Throwable) {
            return false;
        }
    }
}
