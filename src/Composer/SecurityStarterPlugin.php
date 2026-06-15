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

        $this->promptAuditingDriver();
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

    protected function promptAuditingDriver(): void
    {
        if (! $this->io->isInteractive()) {
            $this->io->write('<comment>Run `php artisan security:install` to choose an auditing library.</comment>');

            return;
        }

        $choices = [
            'activitylog' => 'Spatie Activity Log (lightweight — key events only)',
            'auditing' => 'Owen-It Auditing (full model change history)',
            'none' => 'None (security_events table only)',
        ];

        $selected = $this->io->select(
            '<question>Which auditing library would you like to install for pitbphp/security-starter?</question>',
            $choices,
            'activitylog'
        );

        $this->setEnvValue('SECURITY_AUDIT_DRIVER', $selected);

        $packages = [
            'activitylog' => 'spatie/laravel-activitylog:^4.0',
            'auditing' => 'owen-it/laravel-auditing:^13.0',
        ];

        if ($selected === 'none' || ! isset($packages[$selected])) {
            $this->io->write("<info>Auditing driver set to <comment>{$selected}</comment>. Run <comment>php artisan security:install</comment> to publish config and migrate.</info>");

            return;
        }

        if ($this->isPackageInstalled($packages[$selected])) {
            $this->io->write("<info>{$packages[$selected]} is already installed.</info>");

            return;
        }

        $this->io->write("<info>Installing {$packages[$selected]}...</info>");

        $composer = $this->findComposerBinary();
        $command = sprintf('%s require %s --no-interaction', $composer, escapeshellarg($packages[$selected]));
        $exitCode = 0;
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->io->writeError("<error>Failed to install {$packages[$selected]}. Run manually: composer require {$packages[$selected]}</error>");
        } else {
            $this->io->write('<info>Auditing library installed. Run `php artisan security:install` to finish setup.</info>');
        }
    }

    protected function isPackageInstalled(string $constraint): bool
    {
        $name = explode(':', $constraint)[0];

        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ($package->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    protected function setEnvValue(string $key, string $value): void
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $envPath = dirname($vendorDir).DIRECTORY_SEPARATOR.'.env';

        if (! is_file($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);
        $line = "{$key}={$value}";

        if (preg_match("/^{$key}=.*/m", $contents)) {
            $contents = preg_replace("/^{$key}=.*/m", $line, $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($envPath, $contents);
        $this->io->write("<info>Set {$key}={$value} in .env</info>");
    }

    protected function findComposerBinary(): string
    {
        return escapeshellarg(PHP_BINARY).' '.escapeshellarg(
            defined('COMPOSER_BINARY') ? COMPOSER_BINARY : 'composer'
        );
    }
}
