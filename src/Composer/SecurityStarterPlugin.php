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

class SecurityStarterPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE = 'pitbphp/security-starter';

    private Composer $composer;

    private IOInterface $io;

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

        $this->io->write(
            '<info>pitbphp/security-starter installed. Run <comment>php artisan security:install</comment> to complete setup.</info>'
        );
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
