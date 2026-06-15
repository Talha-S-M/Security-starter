<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeederService
{
    public function isAvailable(): bool
    {
        return class_exists(Role::class) && class_exists(Permission::class);
    }

    public function seed(): array
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('spatie/laravel-permission is not installed.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('security.permissions.guard', 'web');
        $created = ['permissions' => 0, 'roles' => 0];

        foreach (config('security.permissions.permissions', []) as $permission) {
            Permission::findOrCreate($permission, $guard);
            $created['permissions']++;
        }

        foreach (config('security.permissions.roles', []) as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, $guard);
            $created['roles']++;

            if ($rolePermissions === ['*']) {
                $role->syncPermissions(Permission::where('guard_name', $guard)->get());

                continue;
            }

            $role->syncPermissions($rolePermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $created;
    }

    public function assignDefaultRole(Authenticatable $user, ?string $role = null): void
    {
        if (! method_exists($user, 'assignRole')) {
            return;
        }

        $role ??= config('security.permissions.default_user_role', 'user');

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
