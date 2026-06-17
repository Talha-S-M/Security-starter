<?php

namespace Pitbphp\Security\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\SecurityEventLogger;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class LogAuthorizationEvents
{
    public function __construct(
        protected SecurityEventLogger $logger
    ) {}

    public function handleRoleAttached(RoleAttached $event): void
    {
        $this->logRbacChange('rbac.role.attached', $event->model, $event->role?->name ?? $event->roles ?? null);
    }

    public function handleRoleDetached(RoleDetached $event): void
    {
        $this->logRbacChange('rbac.role.detached', $event->model, $event->role?->name ?? $event->roles ?? null);
    }

    public function handlePermissionAttached(PermissionAttached $event): void
    {
        $this->logRbacChange('rbac.permission.attached', $event->model, $event->permission?->name ?? $event->permissions ?? null);
    }

    public function handlePermissionDetached(PermissionDetached $event): void
    {
        $this->logRbacChange('rbac.permission.detached', $event->model, $event->permission?->name ?? $event->permissions ?? null);
    }

    protected function logRbacChange(string $event, mixed $subject, mixed $roleOrPermission): void
    {
        if (! config('security.permissions.enabled', true)) {
            return;
        }

        $causer = Auth::user();

        if (! $subject instanceof Model) {
            return;
        }

        $name = is_object($roleOrPermission)
            ? ($roleOrPermission->name ?? null)
            : (is_array($roleOrPermission) ? implode(', ', $roleOrPermission) : $roleOrPermission);

        $extra = [
            'name' => $name,
            'subject_id' => $subject->getKey(),
            'subject_type' => $subject::class,
        ];

        if ($subject instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            $this->logger->rbac($event, true, $subject, $causer, $extra);

            return;
        }

        if (isset($subject->name)) {
            $extra['role'] = $subject->name;
        }

        $this->logger->rbac($event, true, $subject, $causer, $extra);
    }
}
