<?php

namespace Pitbphp\Security\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SecurityRoutePublisher
{
    /**
     * @return array<int, string>
     */
    public static function routeFilesForMode(string $mode): array
    {
        $api = ['security-api.php', 'security-api-auth.php'];
        $web = ['auth.php', 'security.php', 'security-admin.php'];

        return match ($mode) {
            'api' => $api,
            'web' => $web,
            'hybrid' => array_merge($api, $web),
            default => [],
        };
    }

    public static function readmeSourceForMode(string $mode): string
    {
        return match ($mode) {
            'api' => 'README-api.md',
            'web' => 'README-web.md',
            'hybrid' => 'README-hybrid.md',
            default => 'README.md',
        };
    }

    public static function publish(Command $command, string $mode, bool $force = false): void
    {
        $files = self::routeFilesForMode($mode);

        if ($files === []) {
            $command->warn("Skipping route publish: unknown mode [{$mode}].");

            return;
        }

        $sourceDir = dirname(__DIR__, 2).'/routes';
        $readmeSourceDir = $sourceDir.'/pitb-security';
        $destDir = base_path('routes/pitb-security');

        if (! is_dir($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $command->info("Publishing {$mode} route files to routes/pitb-security/...");

        foreach ($files as $filename) {
            $from = $sourceDir.'/'.$filename;
            $to = $destDir.'/'.$filename;

            if (! is_file($from)) {
                $command->warn("  Skipped missing package route: {$filename}");

                continue;
            }

            if (is_file($to) && ! $force) {
                $command->line("  Skipped {$filename} (already exists).");

                continue;
            }

            File::copy($from, $to);
            $command->line("  Published {$filename}");
        }

        $readmeFrom = $readmeSourceDir.'/'.self::readmeSourceForMode($mode);
        $readmeTo = $destDir.'/README.md';

        if (is_file($readmeFrom)) {
            if (! is_file($readmeTo) || $force) {
                File::copy($readmeFrom, $readmeTo);
                $command->line('  Published README.md ('.$mode.' mode reference)');
            } else {
                $command->line('  Skipped README.md (already exists).');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function publishMapForMode(string $mode): array
    {
        $map = [];
        $sourceDir = dirname(__DIR__, 2).'/routes';
        $readmeSourceDir = $sourceDir.'/pitb-security';

        foreach (self::routeFilesForMode($mode) as $filename) {
            $map[$sourceDir.'/'.$filename] = base_path('routes/pitb-security/'.$filename);
        }

        $readme = self::readmeSourceForMode($mode);
        $map[$readmeSourceDir.'/'.$readme] = base_path('routes/pitb-security/README.md');

        return $map;
    }
}
