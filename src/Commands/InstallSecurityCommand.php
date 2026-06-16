<?php



namespace Pitbphp\Security\Commands;



use Illuminate\Console\Command;

use Pitbphp\Security\Support\AuditingPackageResolver;

use Symfony\Component\Process\Process;



class InstallSecurityCommand extends Command

{

    protected $signature = 'security:install

                            {--driver= : Auditing driver: activitylog, auditing, or none}
                            {--mode= : Security mode: web, api, or hybrid}

                            {--skip-composer : Do not run composer require}

                            {--skip-seed : Do not seed default roles and permissions}

                            {--force : Overwrite published files}';



    protected $description = 'Choose auditing library, publish assets, migrate, and seed RBAC defaults';



    public function handle(): int

    {

        $driver = $this->resolveDriver();



        if (! in_array($driver, ['activitylog', 'auditing', 'none'], true)) {

            $this->error('Invalid driver. Use activitylog, auditing, or none.');



            return self::FAILURE;

        }



        $this->updateEnv('SECURITY_AUDIT_DRIVER', $driver);
        $this->updateEnv('SECURITY_MODE', $this->resolveMode());



        if (! $this->option('skip-composer')) {

            $this->installAuditingPackage($driver);

        }



        $this->publishAssets();

        $this->runPackageMigrations();

        $this->runPermissionMigrations();

        $this->seedRbacDefaults();



        $this->newLine();

        $this->info('PITB Security Starter installed.');

        $this->line('Add HasPitbSecurity to your User model and set SECURITY_MAIL_TO in .env.');

        $this->line('Default roles: super-admin, admin, manager, user, vendor.');
        $this->line('Partial routes: /'.config('security.admin.path_prefix', 'security/admin/partials'));



        return self::SUCCESS;

    }



    protected function resolveDriver(): string

    {

        if ($driver = $this->option('driver')) {

            return $driver;

        }



        $current = trim((string) env('SECURITY_AUDIT_DRIVER', ''));



        if ($current !== '' && in_array($current, ['activitylog', 'auditing', 'none'], true)) {

            $this->info("Using existing SECURITY_AUDIT_DRIVER={$current}");



            return $current;

        }



        return $this->choice(

            'Which auditing library would you like to use?',

            ['activitylog', 'auditing', 'none'],

            0

        );

    }

    protected function resolveMode(): string
    {
        if ($mode = $this->option('mode')) {
            return $mode;
        }

        $current = trim((string) env('SECURITY_MODE', ''));

        if ($current !== '' && in_array($current, ['web', 'api', 'hybrid'], true)) {
            $this->info("Using existing SECURITY_MODE={$current}");
            return $current;
        }

        return $this->choice(
            'Which runtime mode do you want to secure?',
            ['web', 'api', 'hybrid'],
            0
        );
    }



    protected function publishAssets(): void

    {

        $force = (bool) $this->option('force');



        $this->call('vendor:publish', [

            '--tag' => 'security-config',

            '--force' => $force,

        ]);



        $this->call('vendor:publish', [

            '--tag' => 'security-views',

            '--force' => $force,

        ]);



        $this->call('vendor:publish', [

            '--tag' => 'security-migrations',

            '--force' => $force,

        ]);



        if (config('security.permissions.enabled', true)) {

            $this->call('vendor:publish', [

                '--provider' => 'Spatie\Permission\PermissionServiceProvider',

                '--force' => $force,

            ]);

        }

    }



    protected function runPackageMigrations(): void

    {

        $path = $this->publishedMigrationPath() ?? $this->packageMigrationPath();



        if (! $path) {

            $this->warn('No migration files found for pitbphp/security-starter.');



            return;

        }



        $this->info("Running migrations from: {$path}");



        $this->call('migrate', [

            '--path' => $path,

            '--realpath' => true,

            '--force' => $this->option('force'),

        ]);

    }



    protected function runPermissionMigrations(): void

    {

        if (! config('security.permissions.enabled', true)) {

            return;

        }



        $this->info('Running permission table migrations...');



        $this->call('migrate', [

            '--force' => $this->option('force'),

        ]);

    }



    protected function seedRbacDefaults(): void

    {

        if ($this->option('skip-seed') || ! config('security.permissions.seed_on_install', true)) {

            return;

        }



        $this->call('security:seed-rbac');

    }



    protected function publishedMigrationPath(): ?string

    {

        $path = database_path('migrations/pitb_security');



        return is_dir($path) ? $path : null;

    }



    protected function packageMigrationPath(): ?string

    {

        $path = realpath(__DIR__.'/../../database/migrations');



        return $path && is_dir($path) ? $path : null;

    }



    protected function installAuditingPackage(string $driver): void

    {

        if ($driver === 'none') {

            return;

        }



        if (AuditingPackageResolver::isPackageAvailable($driver)) {

            $this->info("Auditing package for [{$driver}] is already installed.");



            return;

        }



        $package = AuditingPackageResolver::driverPackage($driver);



        if (! $package) {

            return;

        }



        if (! $this->option('no-interaction') && ! $this->confirm("Install {$package} via Composer?", true)) {

            $this->warn("Skipped. Run manually: composer require {$package}");



            return;

        }



        $process = Process::fromShellCommandline(

            'composer require '.$package,

            base_path()

        );

        $process->setTimeout(600);

        $process->run(fn ($type, $buffer) => $this->output->write($buffer));



        if (! $process->isSuccessful()) {

            $this->warn("Could not install automatically. Run: composer require {$package}");

        }

    }



    protected function updateEnv(string $key, string $value): void

    {

        $path = base_path('.env');



        if (! is_file($path)) {

            $this->warn('.env file not found. Set '.$key.'='.$value.' manually.');



            return;

        }



        $contents = file_get_contents($path);

        $line = $key.'='.$value;

        $pattern = '/^'.preg_quote($key, '/').'=.*/m';



        if (preg_match($pattern, $contents)) {

            $contents = preg_replace($pattern, $line, $contents);

        } else {

            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;

        }



        file_put_contents($path, $contents);

        $this->info("Set {$key}={$value}");

    }

}


