<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
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

    public function recordFailure(Authenticatable $user): void
    {
        if (! $this->hasColumn('failed_login_attempts')) {
            return;
        }

        $attempts = ((int) ($user->failed_login_attempts ?? 0)) + 1;
        $max = (int) config('security.lockout.max_attempts', 5);

        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts >= $max) {
            $updates['locked_until'] = now()->addMinutes(
                (int) config('security.lockout.decay_minutes', 30)
            );
        }

        $this->updateUser($user, $updates);
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
