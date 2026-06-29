<?php

namespace Pitbphp\Security\Support;

class RouteLoader
{
    public static function path(string $filename): string
    {
        $published = base_path('routes/pitb-security/'.$filename);

        if (is_file($published)) {
            return $published;
        }

        return dirname(__DIR__, 2).'/routes/'.$filename;
    }
}
