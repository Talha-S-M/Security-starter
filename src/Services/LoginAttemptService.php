<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoginAttemptService
{
    public function isLocked(Authenticatable $user): bool
    {
        if (! $this->hasColumn('locked_until')) {
            return false;
        }

        $lockedUntil = $user->locked_until ?? null;

        return $lockedUntil && Carbon::parse($lockedUntil)->isFuture();
    }

    public function recordFailure(Authenticatable $user, ?string $identifier = null, ?string $ip = null): void
    {
        $this->recordNetworkFailure($identifier, $ip);

        if (! $this->hasColumn('failed_login_attempts')) {
            return;
        }

        $attempts = ((int) ($user->failed_login_attempts ?? 0)) + 1;
        $max = (int) config('security.lockout.max_attempts', 5);
        $lockMinutes = $this->lockMinutesForAttempts($attempts);

        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts >= $max) {
            $updates['locked_until'] = now()->addMinutes($lockMinutes);

            if (class_exists(\Pitbphp\Security\Services\SecurityEventLogger::class)) {
                app(\Pitbphp\Security\Services\SecurityEventLogger::class)->auth('auth.account_locked', false, $user, [
                    'attempts' => $attempts,
                    'locked_until' => $updates['locked_until']->toIso8601String(),
                    'email' => $identifier,
                ]);
            }
        }

        $this->updateUser($user, $updates);
    }

    public function recordNetworkFailure(?string $identifier = null, ?string $ip = null): int
    {
        $identifier = trim(strtolower((string) $identifier));
        $ip = trim((string) $ip);
        $max = (int) config('security.lockout.ip_max_attempts', 20);
        $decay = (int) config('security.lockout.ip_decay_minutes', 15);

        if ($identifier === '' || $ip === '') {
            return 0;
        }

        $key = "pitb_security_lockout_{$ip}_{$identifier}";
        $attempts = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes($decay));

        return $attempts >= $max ? $attempts : 0;
    }

    public function clear(Authenticatable $user): void
    {
        $updates = [];

        if ($this->hasColumn('failed_login_attempts')) {
            $updates['failed_login_attempts'] = 0;
        }

        if ($this->hasColumn('locked_until')) {
            $updates['locked_until'] = null;
        }

        if ($updates !== []) {
            $this->updateUser($user, $updates);
        }
    }

    protected function lockMinutesForAttempts(int $attempts): int
    {
        $default = (int) config('security.lockout.decay_minutes', 30);
        $progressive = (array) config('security.lockout.progressive', []);

        if ($progressive === []) {
            return $default;
        }

        ksort($progressive, SORT_NUMERIC);
        $minutes = $default;

        foreach ($progressive as $threshold => $duration) {
            if ($attempts >= (int) $threshold) {
                $minutes = (int) $duration;
            }
        }

        return max(1, $minutes);
    }

    protected function updateUser(Authenticatable $user, array $attributes): void
    {
        $table = config('security.user.table', 'users');

        DB::table($table)
            ->where('id', $user->getAuthIdentifier())
            ->update($attributes);

        foreach ($attributes as $key => $value) {
            $user->{$key} = $value;
        }
    }

    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->userColumns(), true);
    }

    protected function userColumns(): array
    {
        static $columns = null;

        if ($columns === null) {
            $columns = DB::getSchemaBuilder()
                ->getColumnListing(config('security.user.table', 'users'));
        }

        return $columns;
    }
}
