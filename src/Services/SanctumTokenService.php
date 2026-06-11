<?php

namespace Pitbphp\Security\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SanctumTokenService
{
    public function isAvailable(): bool
    {
        return trait_exists(\Laravel\Sanctum\HasApiTokens::class);
    }

    public function revokeCurrent(Request $request): void
    {
        if (! config('security.api.revoke_token_on_violation', true)) {
            return;
        }

        $user = $request->user();

        if (! $user || ! method_exists($user, 'currentAccessToken')) {
            return;
        }

        $token = $user->currentAccessToken();

        if ($token) {
            $this->forgetTokenActivity((string) $token->id);
            $token->delete();
        }
    }

    public function touch(Request $request): void
    {
        $tokenId = $this->currentTokenId($request);

        if (! $tokenId) {
            return;
        }

        $timeout = (int) config('security.api.token_idle_timeout_minutes', 0);

        if ($timeout <= 0) {
            return;
        }

        Cache::put(
            $this->activityCacheKey($tokenId),
            now()->toDateTimeString(),
            now()->addMinutes($timeout + 5)
        );
    }

    public function isIdleExpired(Request $request): bool
    {
        $tokenId = $this->currentTokenId($request);

        if (! $tokenId) {
            return false;
        }

        $timeout = (int) config('security.api.token_idle_timeout_minutes', 0);

        if ($timeout <= 0) {
            return false;
        }

        $lastActivity = Cache::get($this->activityCacheKey($tokenId));

        if (! $lastActivity) {
            return false;
        }

        return \Illuminate\Support\Carbon::parse($lastActivity)->addMinutes($timeout)->isPast();
    }

    public function forgetTokenActivity(string $tokenId): void
    {
        Cache::forget($this->activityCacheKey($tokenId));
    }

    public function currentTokenId(Request $request): ?string
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();

        return $token ? (string) $token->id : null;
    }

    protected function activityCacheKey(string $tokenId): string
    {
        return 'pitb_security_token_activity_'.$tokenId;
    }
}
