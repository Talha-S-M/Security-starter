<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Pitbphp\Security\Notifications\MfaOtpNotification;
use Pitbphp\Security\Notifications\MfaOtpSmsNotification;
use Pitbphp\Security\Support\SecurityRequest;

class MfaService
{
    public function issue(Authenticatable $user, ?string $tokenId = null, string $sourceType = 'mfa_otp'): void
    {
        $length = (int) config('security.mfa.otp_length', 6);
        $otp = $this->generateOtp($length);

        Cache::put(
            $this->otpCacheKey($user, $tokenId),
            Hash::make($otp),
            now()->addMinutes((int) config('security.mfa.otp_expiry_minutes', 5))
        );

        if (method_exists($user, 'notify')) {
            $this->sendOtp($user, $otp, $sourceType);
        }
    }

    public function preferredMethod(Authenticatable $user): string
    {
        $method = $user->mfa_method ?? config('security.mfa.default_method', 'email');

        if (! in_array($method, config('security.mfa.methods', ['email', 'sms']), true)) {
            return config('security.mfa.default_method', 'email');
        }

        return $method;
    }

    protected function sendOtp(Authenticatable $user, string $otp, string $sourceType = 'mfa_otp'): void
    {
        if ($this->preferredMethod($user) === 'sms') {
            $user->notify(new MfaOtpSmsNotification($otp, $sourceType));

            return;
        }

        $email = method_exists($user, 'mfaDeliveryEmail')
            ? $user->mfaDeliveryEmail()
            : $user->email;

        Notification::route('mail', $email)->notify(new MfaOtpNotification($otp));
    }

    public function verify(Authenticatable $user, string $otp, ?string $tokenId = null): bool
    {
        $key = $this->otpCacheKey($user, $tokenId);
        $hashed = Cache::get($key);

        if (! $hashed || ! Hash::check($otp, $hashed)) {
            return false;
        }

        Cache::forget($key);
        $this->markVerified($user, $tokenId);

        return true;
    }

    public function isVerified(Authenticatable $user, ?Request $request = null): bool
    {
        $request ??= request();

        if (SecurityRequest::isApi($request)) {
            return $this->isVerifiedForToken($user, SecurityRequest::currentTokenId($request));
        }

        return (bool) $request->session()->get('security.mfa_verified');
    }

    public function markVerified(Authenticatable $user, ?string $tokenId = null): void
    {
        if ($tokenId !== null) {
            $this->markVerifiedForToken($user, $tokenId);

            return;
        }

        request()->session()->put('security.mfa_verified', true);
        request()->session()->forget('security.mfa_issued');
    }

    public function isVerifiedForToken(Authenticatable $user, ?string $tokenId): bool
    {
        if (! $tokenId) {
            return false;
        }

        return (bool) Cache::get($this->verifiedCacheKey($user, $tokenId));
    }

    public function markVerifiedForToken(Authenticatable $user, string $tokenId): void
    {
        Cache::put(
            $this->verifiedCacheKey($user, $tokenId),
            true,
            now()->addDays((int) config('security.api.token_mfa_verified_ttl_days', 30))
        );
    }

    public function clearVerification(Authenticatable $user, ?string $tokenId = null): void
    {
        if ($tokenId) {
            Cache::forget($this->verifiedCacheKey($user, $tokenId));

            return;
        }

        request()->session()->forget(['security.mfa_verified', 'security.mfa_issued']);
    }

    protected function otpCacheKey(Authenticatable $user, ?string $tokenId = null): string
    {
        $suffix = $tokenId ? "_token_{$tokenId}" : '';

        return 'pitb_security_mfa_'.$user->getAuthIdentifier().$suffix;
    }

    protected function verifiedCacheKey(Authenticatable $user, string $tokenId): string
    {
        return 'pitb_security_mfa_verified_'.$user->getAuthIdentifier().'_'.$tokenId;
    }

    protected function generateOtp(int $length): string
    {
        $max = (int) str_repeat('9', $length);
        $min = (int) str_pad('1', $length, '0');

        return (string) random_int($min, $max);
    }
}
