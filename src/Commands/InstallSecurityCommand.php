<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallSecurityCommand extends Command
{
    protected $signature = 'security:install
                            {--driver= : Auditing driver: activitylog, auditing, or none}
                            {--skip-composer : Do not run composer require}';

    protected $description = 'Choose auditing library, publish config/views, and run migrations';

    public function handle(): int
    {
        $driver = $this->option('driver') ?: $this->choice(
            'Which auditing library would you like to use?',
            ['activitylog', 'auditing', 'none'],
            0
        );

        if (! in_array($driver, ['activitylog', 'auditing', 'none'], true)) {
            $this->error('Invalid driver. Use activitylog, auditing, or none.');

            return self::FAILURE;
        }

        $this->updateEnv('SECURITY_AUDIT_DRIVER', $driver);

        if (! $this->option('skip-composer')) {
            $this->installAuditingPackage($driver);
        }

        $this->call('vendor:publish', ['--tag' => 'security-config', '--force' => false]);
        $this->call('vendor:publish', ['--tag' => 'security-views', '--force' => false]);

        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('PITB Security Starter installed.');
        $this->line('Add HasPitbSecurity to your User model and set SECURITY_MAIL_TO in .env.');

        return self::SUCCESS;
    }

    protected function installAuditingPackage(string $driver): void
    {
        $packages = [
            'activitylog' => 'spatie/laravel-activitylog:^4.0',
            'auditing' => 'owen-it/laravel-auditing:^13.0',
        ];

        if (! isset($packages[$driver])) {
            return;
        }

        $alreadyInstalled = match ($driver) {
            'activitylog' => class_exists(\Spatie\Activitylog\Models\Activity::class),
            'auditing' => class_exists(\OwenIt\Auditing\Models\Audit::class),
            default => false,
        };

        if ($alreadyInstalled) {
            $this->info("{$packages[$driver]} is already available.");

            return;
        }

        if (! $this->confirm("Install {$packages[$driver]} via Composer?", true)) {
            return;
        }

        $process = Process::fromShellCommandline(
            'composer require '.$packages[$driver],
            base_path()
        );
        $process->setTimeout(300);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->warn("Could not install automatically. Run: composer require {$packages[$driver]}");
        }
    }

    protected function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);
        $line = "{$key}={$value}";

        if (preg_match("/^{$key}=.*/m", $contents)) {
            $contents = preg_replace("/^{$key}=.*/m", $line, $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $contents);
        $this->info("Set {$key}={$value}");
    }
}
