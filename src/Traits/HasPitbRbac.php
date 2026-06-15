<?php

namespace Pitbphp\Security\Traits;

use Spatie\Permission\Traits\HasRoles;

trait HasPitbRbac
{
    use HasRoles;

    public function isPrivileged(): bool
    {
        return $this->hasAnyRole(
            config('security.permissions.privileged_roles', ['super-admin', 'admin'])
        );
    }

    public function isVendor(): bool
    {
        return $this->hasRole(
            config('security.permissions.vendor_role', 'vendor')
        );
    }
}
