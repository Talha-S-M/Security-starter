<?php

namespace Pitbphp\Security\Traits;

use Illuminate\Support\Carbon;

trait HasPitbSecurity
{
    use HasPasswordHistory;
    use HasPitbRbac;

    public function isSecurityActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    public function isPasswordExpired(): bool
    {
        $days = (int) config('security.password.expiry_days', 90);

        if ($days <= 0) {
            return false;
        }

        if ((bool) ($this->must_change_password ?? false)) {
            return true;
        }

        if (! ($this->password_changed_at ?? null)) {
            return true;
        }

        return Carbon::parse($this->password_changed_at)->addDays($days)->isPast();
    }

    public function hasExpiredAccess(): bool
    {
        if (! isset($this->access_expires_at) || ! $this->access_expires_at) {
            return false;
        }

        return Carbon::parse($this->access_expires_at)->isPast();
    }
}
