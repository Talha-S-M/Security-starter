<?php

namespace Pitbphp\Security\Listeners;

use Illuminate\Auth\Events\Registered;
use Pitbphp\Security\Services\RbacSeederService;

class AssignDefaultRole
{
    public function __construct(
        protected RbacSeederService $rbac
    ) {}

    public function handle(Registered $event): void
    {
        if (! config('security.permissions.enabled', true)) {
            return;
        }

        $this->rbac->assignDefaultRole($event->user);
    }
}
