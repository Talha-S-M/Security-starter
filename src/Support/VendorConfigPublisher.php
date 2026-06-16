<?php

namespace Pitbphp\Security\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VendorConfigPublisher
{
    /**
     * Publish third-party config files used by this package.
     * Skips files that already exist unless $force is true.
     *
     * @return array<int, string>
     */
    public static function publish(Command $command, string $auditDriver, bool $force = false): array
    {
        $published = [];

        $map = [
            'captcha' => [
                'provider' => 'Mews\Captcha\CaptchaServiceProvider',
                'path' => config_path('captcha.php'),
                'label' => 'mews/captcha',
            ],
        ];

        if (config('security.permissions.enabled', true)) {
            $map['permission'] = [
                'provider' => 'Spatie\Permission\PermissionServiceProvider',
                'path' => config_path('permission.php'),
                'label' => 'spatie/laravel-permission',
            ];
        }

        if ($auditDriver === 'activitylog') {
            $map['activitylog'] = [
                'tag' => 'activitylog-config',
                'path' => config_path('activitylog.php'),
                'label' => 'spatie/laravel-activitylog',
            ];
        }

        if ($auditDriver === 'auditing') {
            $map['auditing'] = [
                'provider' => 'OwenIt\Auditing\AuditingServiceProvider',
                'path' => config_path('audit.php'),
                'label' => 'owen-it/laravel-auditing',
            ];
        }

        foreach ($map as $key => $item) {
            if (! $force && File::exists($item['path'])) {
                $command->line("  Skipped {$item['label']} config (already exists).");

                continue;
            }

            $args = ['--force' => $force];

            if (isset($item['provider'])) {
                $args['--provider'] = $item['provider'];
            }

            if (isset($item['tag'])) {
                $args['--tag'] = $item['tag'];
            }

            $command->call('vendor:publish', $args);
            $published[] = $key;
            $command->info("  Published {$item['label']} config.");
        }

        return $published;
    }
}
