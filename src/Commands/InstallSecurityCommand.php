<?php



namespace Pitbphp\Security\Commands;



use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use Pitbphp\Security\Support\AuditingPackageResolver;
use Pitbphp\Security\Support\AuditingMigrationPublisher;
use Pitbphp\Security\Support\InstallMarker;
use Pitbphp\Security\Support\InstallOptionResolver;
use Pitbphp\Security\Support\SanctumInstaller;
use Pitbphp\Security\Support\SecurityTier;
use Pitbphp\Security\Support\SecurityRoutes;
use Pitbphp\Security\Support\VendorConfigPublisher;

use Symfony\Component\Process\Process;



class InstallSecurityCommand extends Command

{

    protected $signature = 'security:install

                            {--driver= : Auditing driver: activitylog, auditing, or none}
                            {--mode= : Security mode: web, api, or hybrid}
                            {--tier= : Security tier: strict, moderate, or minimal}

                            {--skip-composer : Do not run composer require}

                            {--skip-seed : Do not seed default roles and permissions}
                            {--seed : Run default roles and permissions seeder without confirmation}

                            {--force : Overwrite published files}';



    protected $description = 'Choose auditing library, publish assets, migrate, and seed RBAC defaults';



    public function handle(): int

    {

        $driver = $this->resolveDriver();

        if (! in_array($driver, InstallOptionResolver::DRIVERS, true)) {
            $this->error('Invalid driver. Use activitylog, auditing, or none.');

            return self::FAILURE;
        }

        $mode = $this->resolveMode();

        if (! in_array($mode, InstallOptionResolver::MODES, true)) {
            $this->error('Invalid mode. Use web, api, or hybrid.');

            return self::FAILURE;
        }

        $tier = $this->resolveTier();

        if (! in_array($tier, SecurityTier::validTiers(), true)) {
            $this->error('Invalid tier. Use strict, moderate, or minimal.');

            return self::FAILURE;
        }

        if ($this->optionsProvidedViaCli()) {
            $this->info("Using install choices: driver={$driver}, mode={$mode}, tier={$tier}");
        }

        $this->updateEnv('SECURITY_AUDIT_DRIVER', $driver);
        $this->updateEnv('SECURITY_MODE', $mode);
        $this->updateEnv('SECURITY_TIER', $tier);



        if (! $this->option('skip-composer')) {

            $this->installAuditingPackage($driver);

            if (in_array($mode, ['api', 'hybrid'], true)) {
                $this->installSanctumPackage();
            }

        }

        if (in_array($mode, ['api', 'hybrid'], true) && SanctumInstaller::isAvailable()) {
            SanctumInstaller::publish($this, (bool) $this->option('force'));
        }



        $this->publishAssets($mode);
        $this->publishVendorConfigs($driver);
        $this->publishAuditingMigrations($driver);

        $this->ensureDefaultLaravelMigrations();
        $this->runPackageMigrations();

        $this->runApplicationMigrations();

        $this->seedRbacDefaults();



        $this->newLine();

        $this->info('PITB Security Starter installed.');

        $this->line('Add HasPitbSecurity to your User model and set SECURITY_MAIL_TO in .env.');

        $this->line('Default roles: super-admin, admin, manager, user, vendor.');

        if (in_array($mode, ['web', 'hybrid'], true)) {
            $this->line('Partial routes: /'.config('security.admin.path_prefix', 'security/admin/partials'));
        }

        if (in_array($mode, ['api', 'hybrid'], true)) {
            $this->line('Sanctum auth: POST /'.SecurityRoutes::apiAuthPath('login').' (token), GET /sanctum/csrf-cookie (SPA)');
            $this->line('Security API: /'.trim(config('security.api.path_prefix', 'api/security'), '/'));
            $this->line('Route reference: routes/pitb-security/README.md — run php artisan security:routes');
            if ($mode === 'api') {
                $this->line('Views were not published — API mode uses JSON responses only.');
            }
            if (! SanctumInstaller::userHasApiTokens()) {
                $this->warn('Add Laravel\\Sanctum\\HasApiTokens to your User model for token authentication.');
            }
        }

        InstallMarker::write();

        return self::SUCCESS;

    }



    protected function resolveDriver(): string
    {
        if ($this->option('driver') !== null && $this->option('driver') !== '') {
            $normalized = InstallOptionResolver::normalizeDriver($this->option('driver'));

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $current = trim((string) env('SECURITY_AUDIT_DRIVER', ''));

        if ($current !== '' && ($normalized = InstallOptionResolver::normalizeDriver($current)) !== null) {
            $this->info("Using existing SECURITY_AUDIT_DRIVER={$normalized}");

            return $normalized;
        }

        $selected = $this->choice(
            'Which auditing library would you like to use?',
            InstallOptionResolver::driverChoices(),
            'activitylog'
        );

        return InstallOptionResolver::normalizeSelectValue($selected, InstallOptionResolver::driverChoices())
            ?? 'activitylog';
    }

    protected function resolveMode(): string
    {
        if ($this->option('mode') !== null && $this->option('mode') !== '') {
            $normalized = InstallOptionResolver::normalizeMode($this->option('mode'));

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $current = trim((string) env('SECURITY_MODE', ''));

        if ($current !== '' && ($normalized = InstallOptionResolver::normalizeMode($current)) !== null) {
            $this->info("Using existing SECURITY_MODE={$normalized}");

            return $normalized;
        }

        $selected = $this->choice(
            'Which runtime mode do you want to secure?',
            InstallOptionResolver::modeChoices(),
            'web'
        );

        return InstallOptionResolver::normalizeSelectValue($selected, InstallOptionResolver::modeChoices())
            ?? 'web';
    }

    protected function resolveTier(): string
    {
        if ($this->option('tier') !== null && $this->option('tier') !== '') {
            $normalized = InstallOptionResolver::normalizeTier($this->option('tier'));

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $current = trim((string) env('SECURITY_TIER', ''));

        if ($current !== '' && ($normalized = InstallOptionResolver::normalizeTier($current)) !== null) {
            $this->info("Using existing SECURITY_TIER={$normalized}");

            return $normalized;
        }

        $selected = $this->choice(
            'Which security tier do you want?',
            SecurityTier::installChoices(),
            SecurityTier::STRICT
        );

        return InstallOptionResolver::normalizeSelectValue($selected, SecurityTier::installChoices())
            ?? SecurityTier::STRICT;
    }

    protected function optionsProvidedViaCli(): bool
    {
        return $this->option('driver') !== null && $this->option('driver') !== ''
            && $this->option('mode') !== null && $this->option('mode') !== ''
            && $this->option('tier') !== null && $this->option('tier') !== '';
    }



    protected function publishAssets(string $mode): void

    {

        $force = (bool) $this->option('force');



        $this->call('vendor:publish', [

            '--tag' => 'security-config',

            '--force' => $force,

        ]);



        if (in_array($mode, ['web', 'hybrid'], true)) {
            $this->call('vendor:publish', [

                '--tag' => 'security-views',

                '--force' => $force,

            ]);

            $this->call('vendor:publish', [

                '--tag' => 'security-assets',

                '--force' => $force,

            ]);
        } else {
            $this->info('Skipping views and front-end assets (API-only mode).');
        }



        $this->call('vendor:publish', [

            '--tag' => 'security-migrations',

            '--force' => $force,

        ]);

        $this->call('vendor:publish', [

            '--tag' => 'security-routes',

            '--force' => $force,

        ]);

    }

    protected function publishVendorConfigs(string $auditDriver): void
    {
        $this->info('Publishing dependency config files (skipped if already present)...');

        VendorConfigPublisher::publish($this, $auditDriver, (bool) $this->option('force'));
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



    protected function publishAuditingMigrations(string $auditDriver): void
    {
        if ($auditDriver === 'none') {
            return;
        }

        $this->info('Publishing auditing package migrations...');

        AuditingMigrationPublisher::publish($this, $auditDriver, (bool) $this->option('force'));
    }

    protected function runApplicationMigrations(): void
    {
        $this->info('Running application migrations (permissions, activity log, etc.)...');

        $this->call('migrate', [
            '--force' => $this->option('force'),
        ]);
    }

    protected function ensureDefaultLaravelMigrations(): void
    {
        $userTable = (string) config('security.user.table', 'users');

        if (Schema::hasTable($userTable)) {
            return;
        }

        $this->warn("User table [{$userTable}] not found. Running default Laravel migrations first...");

        $this->call('migrate', [
            '--force' => $this->option('force'),
        ]);

        if (! Schema::hasTable($userTable)) {
            $this->warn("User table [{$userTable}] is still missing after migrate. Check your SECURITY_USER_TABLE setting and app migrations.");
        }
    }



    protected function seedRbacDefaults(): void

    {
        if (! config('security.permissions.enabled', true)) {
            $this->warn('RBAC seeding skipped: permissions are disabled in config/security.php.');
            return;
        }

        if ($this->option('skip-seed')) {
            $this->warn('Skipped default RBAC seeding (--skip-seed).');
            $this->showDeferredSeedInstructions();
            return;
        }

        if ($this->option('seed')) {
            $shouldSeed = true;
        } else {
            $default = (bool) config('security.permissions.seed_on_install', true);
            $shouldSeed = $this->option('no-interaction')
                ? $default
                : $this->confirm('Run default roles and permissions seeder now?', $default);
        }

        if (! $shouldSeed) {
            $this->warn('Skipped default RBAC seeding.');
            $this->showDeferredSeedInstructions();
            return;
        }

        $result = $this->call('security:seed-rbac');

        if ($result !== self::SUCCESS) {
            $this->warn('Default RBAC seeding did not complete successfully.');
            $this->showDeferredSeedInstructions();
        }

    }

    protected function showDeferredSeedInstructions(): void
    {
        $this->newLine();
        $this->line('You can seed later after editing roles/permissions in config/security.php:');
        $this->line('  - security.permissions.permissions');
        $this->line('  - security.permissions.roles');
        $this->line('Then run: php artisan security:seed-rbac');
        $this->line('If config is cached, run first: php artisan optimize:clear');
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

        // No version constraint — let Composer resolve a version compatible with the host app.
        $process = new Process(
            array_merge(
                $this->composerCommand(),
                ['require', '--no-interaction', $package]
            ),
            base_path()
        );

        $process->setTimeout(600);

        $process->run(fn ($type, $buffer) => $this->output->write($buffer));



        if (! $process->isSuccessful()) {

            $this->warn("Could not install automatically. Run: composer require {$package}");

        }

    }

    protected function installSanctumPackage(): void
    {
        if (SanctumInstaller::isAvailable()) {
            $this->info('laravel/sanctum is already installed.');

            return;
        }

        $package = SanctumInstaller::PACKAGE;

        if (! $this->option('no-interaction') && ! $this->confirm("Install {$package} via Composer?", true)) {
            $this->warn("Skipped. Run manually: composer require {$package}");

            return;
        }

        $process = new Process(
            array_merge(
                $this->composerCommand(),
                ['require', '--no-interaction', $package]
            ),
            base_path()
        );

        $process->setTimeout(600);

        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->warn("Could not install automatically. Run: composer require {$package}");
        }
    }



    /**
     * @return array<int, string>
     */
    protected function composerCommand(): array
    {
        $local = base_path('composer.phar');

        if (is_file($local)) {
            return [PHP_BINARY, $local];
        }

        return ['composer'];
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


