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
use Pitbphp\Security\Support\InstallMarker;
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

        $this->io->write('<info>Running php artisan security:install...</info>');

        $process = new Process([PHP_BINARY, $artisan, 'security:install'], $basePath);
        $process->setTimeout(null);

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->run(function ($type, $buffer): void {
            $this->io->write($buffer, false);
        });

        if (! $process->isSuccessful()) {
            $this->io->writeError(
                '<error>security:install did not complete successfully. Run <comment>php artisan security:install</comment> manually.</error>'
            );
        }
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
