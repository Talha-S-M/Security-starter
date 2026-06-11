<?php

namespace Pitbphp\Security\Support;

class SecurityRoutes
{
    public static function name(string $route): string
    {
        return config('security.routes.name_prefix', 'security.').$route;
    }

    public static function path(string $path = ''): string
    {
        $prefix = trim(config('security.routes.prefix', 'security'), '/');

        return $path === '' ? $prefix : $prefix.'/'.ltrim($path, '/');
    }

    public static function apiName(string $route): string
    {
        return config('security.api.route_name_prefix', 'security.api.').$route;
    }

    public static function apiPath(string $path = ''): string
    {
        $prefix = trim(config('security.api.path_prefix', 'api/security'), '/');

        return $path === '' ? $prefix : $prefix.'/'.ltrim($path, '/');
    }
}
