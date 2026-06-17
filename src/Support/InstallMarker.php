<?php

namespace Pitbphp\Security\Support;

class InstallMarker
{
    public static function relativePath(): string
    {
        return 'storage/app/pitb-security-installed';
    }

    public static function path(?string $basePath = null): string
    {
        $base = $basePath ?? base_path();

        return $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, self::relativePath());
    }

    public static function exists(?string $basePath = null): bool
    {
        if (is_file(self::path($basePath))) {
            return true;
        }

        $base = $basePath ?? base_path();

        return is_file($base.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'security.php');
    }

    public static function write(?string $basePath = null): void
    {
        $path = self::path($basePath);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM));
    }
}
