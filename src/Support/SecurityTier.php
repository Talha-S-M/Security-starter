<?php

namespace Pitbphp\Security\Support;

/**
 * Resolves security tier presets once at boot so feature code reads
 * flat config keys instead of branching on tier everywhere.
 */
class SecurityTier
{
    public const STRICT = 'strict';

    public const MODERATE = 'moderate';

    public const MINIMAL = 'minimal';

    /**
     * @return array<int, string>
     */
    public static function validTiers(): array
    {
        return [self::STRICT, self::MODERATE, self::MINIMAL];
    }

    /**
     * @return array<string, string>
     */
    public static function installChoices(): array
    {
        return [
            self::STRICT => 'Strict — only admins create users; admin changes need super-admin approval',
            self::MODERATE => 'Moderate — self-registration; new accounts need admin or super-admin approval',
            self::MINIMAL => 'Minimal — self-registration with email OTP; minimal permissions, no approval queue',
        ];
    }

    public static function apply(): void
    {
        $tier = self::current();
        $presets = (array) config("security.tiers.{$tier}", []);

        foreach ($presets as $dotKey => $value) {
            config(["security.{$dotKey}" => $value]);
        }

        if (env('SECURITY_AUTH_REGISTER') !== null && env('SECURITY_AUTH_REGISTER') !== '') {
            config([
                'security.auth.register' => filter_var(
                    env('SECURITY_AUTH_REGISTER'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false,
            ]);
        }
    }

    public static function current(): string
    {
        $tier = strtolower((string) config('security.tier', self::STRICT));

        return in_array($tier, self::validTiers(), true) ? $tier : self::STRICT;
    }

    public static function isStrict(): bool
    {
        return self::current() === self::STRICT;
    }

    public static function isModerate(): bool
    {
        return self::current() === self::MODERATE;
    }

    public static function isMinimal(): bool
    {
        return self::current() === self::MINIMAL;
    }

    public static function registrationEnabled(): bool
    {
        return (bool) config('security.auth.register', false);
    }

    public static function registrationRequiresApproval(): bool
    {
        return (bool) config('security.registration.requires_approval', true);
    }

    public static function registrationUsesOtp(): bool
    {
        return (bool) config('security.registration.otp_verification', false);
    }

    public static function accessProvisioningEnabled(): bool
    {
        return (bool) config('security.access_provisioning.enabled', true);
    }
}
