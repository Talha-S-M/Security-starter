<?php

namespace Pitbphp\Security\Listeners;

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
        $this->logRbacChange('rbac.role.attached', $event->model, $event->role?->name);
    }

    public function handleRoleDetached(RoleDetached $event): void
    {
        $this->logRbacChange('rbac.role.detached', $event->model, $event->role?->name);
    }

    public function handlePermissionAttached(PermissionAttached $event): void
    {
        $this->logRbacChange('rbac.permission.attached', $event->model, $event->permission?->name);
    }

    public function handlePermissionDetached(PermissionDetached $event): void
    {
        $this->logRbacChange('rbac.permission.detached', $event->model, $event->permission?->name);
    }

    protected function logRbacChange(string $event, mixed $subject, mixed $roleOrPermission): void
    {
        if (! config('security.permissions.enabled', true)) {
            return;
        }

        $causer = Auth::user();

        if (! $subject instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return;
        }

        $name = is_object($roleOrPermission) ? ($roleOrPermission->name ?? null) : $roleOrPermission;

        $this->logger->rbac($event, true, $subject, $causer, [
            'name' => $name,
            'subject_id' => $subject->getAuthIdentifier(),
            'subject_type' => $subject::class,
        ]);
    }
}
