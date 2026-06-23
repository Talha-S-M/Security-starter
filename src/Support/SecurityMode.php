<?php

namespace Pitbphp\Security\Support;

/**
 * Applies runtime-mode presets (web / api / hybrid) once at boot.
 */
class SecurityMode
{
    public static function apply(): void
    {
        $mode = SecurityRequest::mode();
        $presets = (array) config("security.modes.{$mode}", []);

        foreach ($presets as $dotKey => $value) {
            config(["security.{$dotKey}" => $value]);
        }
    }
}
