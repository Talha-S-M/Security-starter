<?php

namespace Pitbphp\Security\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use Symfony\Component\Process\Process;

class SecurityStarterPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE = 'pitbphp/security-starter';

    private Composer $composer;

    private IOInterface $io;

    private bool $packageChanged = false;

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
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageChange',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPackageChange',
            ScriptEvents::POST_INSTALL_CMD => 'onPostComposerCommand',
            ScriptEvents::POST_UPDATE_CMD => 'onPostComposerCommand',
        ];
    }

    public function onPackageChange(PackageEvent $event): void
    {
        $package = $this->resolvePackageFromOperation($event);

        if (! $package || $package->getName() !== self::PACKAGE) {
            return;
        }

        if ($this->composer->getPackage()->getName() === self::PACKAGE) {
            return;
        }

        $this->packageChanged = true;
    }

    public function onPostComposerCommand(ScriptEvent $event): void
    {
        if (! $this->packageChanged) {
            return;
        }

        $this->packageChanged = false;

        if ($this->composer->getPackage()->getName() === self::PACKAGE) {
            return;
        }

        $artisan = $this->artisanPath();

        if ($artisan === null) {
            $this->io->write(
                '<comment>pitbphp/security-starter installed, but no artisan file was found. Run security:install from your Laravel app root when ready.</comment>'
            );

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

        $process = new Process([PHP_BINARY, $artisan, 'security:install'], dirname($artisan));
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

    protected function artisanPath(): ?string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $artisan = dirname($vendorDir).DIRECTORY_SEPARATOR.'artisan';

        return is_file($artisan) ? $artisan : null;
    }

    protected function resolvePackageFromOperation(PackageEvent $event): ?PackageInterface
    {
        $operation = $event->getOperation();

        if ($operation instanceof InstallOperation) {
            return $operation->getPackage();
        }

        if ($operation instanceof UpdateOperation) {
            return $operation->getTargetPackage();
        }

        return null;
    }
}
