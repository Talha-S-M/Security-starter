<?php

namespace Pitbphp\Security\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Pitbphp\Security\Notifications\RegistrationOtpNotification;

class RegistrationOtpService
{
    public function issue(string $email): void
    {
        $email = $this->normalizeEmail($email);
        $length = (int) config('security.registration.otp_length', config('security.mfa.otp_length', 6));
        $otp = $this->generateOtp($length);

        Cache::put(
            $this->cacheKey($email),
            Hash::make($otp),
            now()->addMinutes((int) config('security.registration.otp_expiry_minutes', config('security.mfa.otp_expiry_minutes', 5)))
        );

        Notification::route('mail', $email)->notify(new RegistrationOtpNotification($otp));
    }

    public function verify(string $email, string $otp): bool
    {
        $email = $this->normalizeEmail($email);
        $hashed = Cache::get($this->cacheKey($email));

        if (! $hashed || ! Hash::check($otp, $hashed)) {
            return false;
        }

        Cache::forget($this->cacheKey($email));

        return true;
    }

    protected function cacheKey(string $email): string
    {
        return 'pitb_security_registration_otp_'.$email;
    }

    protected function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    protected function generateOtp(int $length): string
    {
        $max = (int) str_repeat('9', $length);
        $min = (int) str_pad('1', $length, '0');

        return (string) random_int($min, $max);
    }
}
