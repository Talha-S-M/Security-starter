<?php

namespace Pitbphp\Security\Support;

use Illuminate\Validation\Rules\Password;

class PasswordStrength
{
    /**
     * Policy values from config/security.php, embedded in the page for client-side checks.
     *
     * @return array<string, bool|int>
     */
    public static function policy(): array
    {
        return [
            'min_length' => (int) config('security.password.min_length', 12),
            'require_uppercase' => (bool) config('security.password.require_uppercase', true),
            'require_lowercase' => (bool) config('security.password.require_lowercase', true),
            'require_numbers' => (bool) config('security.password.require_numbers', true),
            'require_symbols' => (bool) config('security.password.require_symbols', true),
        ];
    }

    public static function firstViolation(string $password): ?string
    {
        $rule = Password::min((int) config('security.password.min_length', 12));

        if (config('security.password.require_uppercase') && config('security.password.require_lowercase')) {
            $rule->mixedCase();
        } elseif (config('security.password.require_uppercase') || config('security.password.require_lowercase')) {
            $rule->letters();
        }

        if (config('security.password.require_numbers')) {
            $rule->numbers();
        }

        if (config('security.password.require_symbols')) {
            $rule->symbols();
        }

        $validator = validator(['password' => $password], ['password' => $rule]);

        if ($validator->fails()) {
            return $validator->errors()->first('password');
        }

        return null;
    }

    public static function suggestedTemporaryPassword(): string
    {
        $configured = config('security.access_provisioning.default_temporary_password');

        if (is_string($configured) && $configured !== '' && self::firstViolation($configured) === null) {
            return $configured;
        }

        return self::generate();
    }

    public static function generate(): string
    {
        $policy = self::policy();
        $length = max((int) $policy['min_length'], 12);
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%&*?';

        $required = '';
        $pool = '';

        if ($policy['require_uppercase']) {
            $required .= $upper[random_int(0, strlen($upper) - 1)];
            $pool .= $upper;
        }

        if ($policy['require_lowercase']) {
            $required .= $lower[random_int(0, strlen($lower) - 1)];
            $pool .= $lower;
        }

        if ($policy['require_numbers']) {
            $required .= $numbers[random_int(0, strlen($numbers) - 1)];
            $pool .= $numbers;
        }

        if ($policy['require_symbols']) {
            $required .= $symbols[random_int(0, strlen($symbols) - 1)];
            $pool .= $symbols;
        }

        if ($pool === '') {
            $pool = $upper.$lower.$numbers.$symbols;
        }

        $password = $required;

        while (strlen($password) < $length) {
            $password .= $pool[random_int(0, strlen($pool) - 1)];
        }

        $password = str_shuffle($password);

        return self::firstViolation($password) === null ? $password : self::generate();
    }
}
