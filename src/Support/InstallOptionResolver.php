<?php

namespace Pitbphp\Security\Support;

/**
 * Normalizes install prompts between Composer plugin and artisan security:install.
 *
 * Composer's IO::select() may return numeric indices for list choices; associative
 * choices return keys. These helpers always resolve to canonical slug values.
 */
class InstallOptionResolver
{
    /** @var array<int, string> */
    public const DRIVERS = ['activitylog', 'auditing', 'none'];

    /** @var array<int, string> */
    public const MODES = ['web', 'api', 'hybrid'];

    /**
     * @return array<string, string>
     */
    public static function driverChoices(): array
    {
        return [
            'activitylog' => 'Activity log (Spatie)',
            'auditing' => 'Model auditing (Owen-It)',
            'none' => 'None',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function modeChoices(): array
    {
        return [
            'web' => 'Web — sessions and Blade views',
            'api' => 'API — Sanctum tokens, JSON only',
            'hybrid' => 'Hybrid — web and API',
        ];
    }

    public static function normalizeDriver(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $index = (int) $value;

            return self::DRIVERS[$index] ?? null;
        }

        $slug = strtolower(trim((string) $value));

        return in_array($slug, self::DRIVERS, true) ? $slug : null;
    }

    public static function normalizeMode(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $index = (int) $value;

            return self::MODES[$index] ?? null;
        }

        $slug = strtolower(trim((string) $value));

        return in_array($slug, self::MODES, true) ? $slug : null;
    }

    public static function normalizeTier(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $valid = SecurityTier::validTiers();

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $index = (int) $value;

            return $valid[$index] ?? null;
        }

        $slug = strtolower(trim((string) $value));

        if (in_array($slug, $valid, true)) {
            return $slug;
        }

        foreach (array_keys(SecurityTier::installChoices()) as $key) {
            if ($slug === strtolower(trim($key))) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $choices
     */
    public static function normalizeSelectValue(mixed $selected, array $choices): ?string
    {
        if ($selected === null || $selected === '') {
            return null;
        }

        if (array_key_exists((string) $selected, $choices)) {
            return (string) $selected;
        }

        if (is_int($selected) || (is_string($selected) && ctype_digit($selected))) {
            $keys = array_keys($choices);
            $index = (int) $selected;

            return $keys[$index] ?? null;
        }

        $needle = strtolower(trim((string) $selected));

        foreach (array_keys($choices) as $key) {
            if ($needle === strtolower(trim($key))) {
                return $key;
            }
        }

        return null;
    }
}
