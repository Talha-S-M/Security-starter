<?php

namespace Pitbphp\Security\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class MfaContactSupport
{
    /**
     * @return array<int, string>
     */
    public static function enabledMethods(): array
    {
        return array_values(array_filter(
            config('security.mfa.methods', ['email', 'sms']),
            fn (string $method) => in_array($method, ['email', 'sms'], true)
        ));
    }

    public static function deliveryEmail(Authenticatable $user): ?string
    {
        $email = trim((string) ($user->mfa_email ?? $user->email ?? ''));

        return $email !== '' ? $email : null;
    }

    public static function deliveryPhone(Authenticatable $user): ?string
    {
        $phone = trim((string) ($user->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }

    /**
     * Methods that can be used right now based on stored contacts.
     *
     * @return array<int, string>
     */
    public static function availableMethods(Authenticatable $user): array
    {
        $methods = [];

        if (in_array('email', self::enabledMethods(), true) && self::deliveryEmail($user)) {
            $methods[] = 'email';
        }

        if (in_array('sms', self::enabledMethods(), true) && self::deliveryPhone($user)) {
            $methods[] = 'sms';
        }

        return $methods;
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeMethods(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter($value, fn ($method) => is_string($method) && $method !== '')));
    }

    /**
     * @return array<int, string>
     */
    public static function resolveMethods(Authenticatable $user): array
    {
        $stored = self::normalizeMethods($user->mfa_methods ?? null);
        $available = self::availableMethods($user);

        if ($stored === []) {
            return $available;
        }

        return array_values(array_intersect($stored, $available));
    }

    public static function resolveDeliveryMethod(Authenticatable $user, ?string $preferred = null): string
    {
        $available = self::resolveMethods($user);

        if ($preferred && in_array($preferred, $available, true)) {
            return $preferred;
        }

        $default = (string) config('security.mfa.default_method', 'email');

        if (in_array($default, $available, true)) {
            return $default;
        }

        return $available[0] ?? $default;
    }

    public static function deliveryLabel(Authenticatable $user, string $method): string
    {
        return match ($method) {
            'sms' => self::deliveryPhone($user) ?? 'SMS',
            default => self::deliveryEmail($user) ?? 'email',
        };
    }

    public static function hasRequiredContact(Authenticatable $user): bool
    {
        return self::availableMethods($user) !== [];
    }
}
