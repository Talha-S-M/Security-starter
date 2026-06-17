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
}
