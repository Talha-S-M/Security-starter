<?php

namespace Pitbphp\Security\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Script\ScriptEvents;
use Pitbphp\Security\Support\AuditingPackageResolver;
use Pitbphp\Security\Support\InstallMarker;
use Pitbphp\Security\Support\SanctumInstaller;
use Pitbphp\Security\Support\SecurityTier;
use Symfony\Component\Process\Process;

class SecurityStarterPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE = 'pitbphp/security-starter';

    private Composer $composer;

    private IOInterface $io;

    private bool $packageInstalled = false;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageInstalled',
            ScriptEvents::POST_INSTALL_CMD => 'onPostComposerInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostComposerInstall',
        ];
    }

    public function onPackageInstalled(PackageEvent $event): void
    {
        if (! $event->getOperation() instanceof InstallOperation) {
            return;
        }

        $package = $event->getOperation()->getPackage();

        if ($package->getName() !== self::PACKAGE) {
            return;
        }

        if ($this->composer->getPackage()->getName() === self::PACKAGE) {
            return;
        }

        $this->packageInstalled = true;
    }

    public function onPostComposerInstall(): void
    {
        if (! $this->packageInstalled) {
            return;
        }

        $this->packageInstalled = false;

        if ($this->composer->getPackage()->getName() === self::PACKAGE) {
            return;
        }

        $basePath = $this->applicationBasePath();
        $artisan = $this->artisanPath($basePath);

        if ($artisan === null) {
            $this->io->write(
                '<comment>pitbphp/security-starter installed, but no artisan file was found. Run security:install from your Laravel app root when ready.</comment>'
            );

            return;
        }

        if (InstallMarker::exists($basePath)) {
            return;
        }

        if (! $this->io->isInteractive()) {
            $this->io->write(
                '<info>pitbphp/security-starter installed. Run <comment>php artisan security:install</comment> to complete setup.</info>'
            );

            return;
        }

        if (! $this->io->askConfirmation(
            '<question>Run php artisan security:install now to complete setup?</question>',
            true
        )) {
            $this->io->write(
                '<info>Skipped. Run <comment>php artisan security:install</comment> when you are ready.</info>'
            );

            return;
        }

        $options = $this->promptInstallOptions();
        $command = [
            PHP_BINARY,
            $artisan,
            'security:install',
            '--driver='.$options['driver'],
            '--mode='.$options['mode'],
            '--tier='.$options['tier'],
        ];

        if ($options['skip_composer']) {
            $command[] = '--skip-composer';
        }

        if ($options['should_seed']) {
            $command[] = '--seed';
        } else {
            $command[] = '--skip-seed';
        }

        $this->io->write('<info>Running php artisan security:install...</info>');

        $process = new Process($command, $basePath);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer): void {
            $this->io->write($buffer, false);
        });

        if (! $process->isSuccessful()) {
            $this->io->writeError(
                '<error>security:install did not complete successfully. Run <comment>php artisan security:install</comment> manually.</error>'
            );
        }
    }

    /**
     * @return array{driver: string, mode: string, tier: string, skip_composer: bool, should_seed: bool}
     */
    protected function promptInstallOptions(): array
    {
        $this->io->write('<info>Configure PITB Security setup:</info>');

        $driver = $this->io->select(
            'Which auditing library would you like to use?',
            ['activitylog', 'auditing', 'none'],
            0
        );

        $mode = $this->io->select(
            'Which runtime mode do you want to secure?',
            ['web', 'api', 'hybrid'],
            0
        );

        $tier = $this->io->select(
            'Which security tier do you want?',
            SecurityTier::installChoices(),
            SecurityTier::STRICT
        );

        $skipComposer = false;

        if ($driver !== 'none' && ! AuditingPackageResolver::isPackageAvailable($driver)) {
            $package = AuditingPackageResolver::driverPackage($driver);

            if ($package && ! $this->io->askConfirmation("Install {$package} via Composer?", true)) {
                $skipComposer = true;
            }
        }

        if (in_array($mode, ['api', 'hybrid'], true) && ! SanctumInstaller::isAvailable()) {
            if (! $this->io->askConfirmation('Install laravel/sanctum via Composer?', true)) {
                $skipComposer = true;
            }
        }

        $shouldSeed = $this->io->askConfirmation(
            'Run default roles and permissions seeder now?',
            true
        );

        return [
            'driver' => (string) $driver,
            'mode' => (string) $mode,
            'tier' => (string) $tier,
            'skip_composer' => $skipComposer,
            'should_seed' => $shouldSeed,
        ];
    }

    protected function applicationBasePath(): string
    {
        return dirname($this->composer->getConfig()->get('vendor-dir'));
    }

    protected function artisanPath(?string $basePath = null): ?string
    {
        $basePath ??= $this->applicationBasePath();
        $artisan = $basePath.DIRECTORY_SEPARATOR.'artisan';

        return is_file($artisan) ? $artisan : null;
    }
}
