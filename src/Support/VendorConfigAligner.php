<?php

namespace Pitbphp\Security\Support;

/**
 * Keeps vendor package config aligned with config/security.php.
 *
 * Source of truth: security.php (+ SECURITY_* env vars).
 * Vendor files (captcha.php, activitylog.php, etc.) are for low-level
 * package settings only; feature toggles are mirrored here at runtime.
 */
class VendorConfigAligner
{
    public static function apply(): void
    {
        if (config()->has('captcha.disable')) {
            config([
                'captcha.disable' => ! (bool) config('security.captcha.enabled', true),
            ]);
        }

        $driver = (string) config('security.auditing.driver', 'activitylog');

        if (config()->has('activitylog.enabled')) {
            config(['activitylog.enabled' => $driver === 'activitylog']);
        }

        if (config()->has('audit.enabled')) {
            config(['audit.enabled' => $driver === 'auditing']);
        }

        if (config('security.permissions.enabled', true)) {
            $guard = (string) config('security.permissions.guard', 'web');

            if (config()->has('permission.defaults.guard_name')) {
                config(['permission.defaults.guard_name' => $guard]);
            }

            if (config()->has('permission.guard_name')) {
                config(['permission.guard_name' => $guard]);
            }
        }

        if (config()->has('activitylog.delete_records_older_than_days')) {
            $months = (int) config('security.logging.retention.audit_trail_months', 12);
            config(['activitylog.delete_records_older_than_days' => max(1, $months * 30)]);
        }

        CaptchaSupport::apply();
        SecurityTier::apply();
        SecurityMode::apply();
    }

    /**
     * @return array<int, string>
     */
    public static function diagnose(): array
    {
        $issues = [];

        $captchaEnabled = (bool) config('security.captcha.enabled', true);
        $captchaDisable = self::envBool('CAPTCHA_DISABLE');

        if ($captchaDisable !== null && $captchaEnabled === $captchaDisable) {
            $issues[] = 'CAPTCHA conflict: SECURITY_CAPTCHA_ENABLED='.self::boolLabel($captchaEnabled)
                .' but CAPTCHA_DISABLE='.self::boolLabel($captchaDisable)
                .' (these must be opposites).';
        }

        $driver = (string) config('security.auditing.driver', 'activitylog');
        $activityEnabled = self::envBool('ACTIVITY_LOGGER_ENABLED');
        $auditingEnabled = self::envBool('AUDITING_ENABLED');

        if ($driver === 'activitylog' && $activityEnabled === false) {
            $issues[] = 'Auditing conflict: SECURITY_AUDIT_DRIVER=activitylog but ACTIVITY_LOGGER_ENABLED=false.';
        }

        if ($driver === 'auditing' && $auditingEnabled === false) {
            $issues[] = 'Auditing conflict: SECURITY_AUDIT_DRIVER=auditing but AUDITING_ENABLED=false.';
        }

        if ($driver === 'none') {
            if ($activityEnabled === true) {
                $issues[] = 'Auditing conflict: SECURITY_AUDIT_DRIVER=none but ACTIVITY_LOGGER_ENABLED=true.';
            }
            if ($auditingEnabled === true) {
                $issues[] = 'Auditing conflict: SECURITY_AUDIT_DRIVER=none but AUDITING_ENABLED=true.';
            }
        }

        if ($driver === 'activitylog' && $auditingEnabled === true) {
            $issues[] = 'Auditing note: SECURITY_AUDIT_DRIVER=activitylog but AUDITING_ENABLED=true (Owen-It may still boot).';
        }

        if (config('security.permissions.enabled', true)) {
            $securityGuard = (string) config('security.permissions.guard', 'web');
            $permissionGuard = env('PERMISSION_GUARD');

            if ($permissionGuard !== null && $permissionGuard !== '' && $permissionGuard !== $securityGuard) {
                $issues[] = "Permission guard conflict: SECURITY_PERMISSIONS_GUARD={$securityGuard} but PERMISSION_GUARD={$permissionGuard}.";
            }
        }

        return $issues;
    }

    protected static function envBool(string $key): ?bool
    {
        $value = env($key);

        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected static function boolLabel(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
