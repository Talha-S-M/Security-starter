<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Notifications\PendingAccessRequestNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class AccessProvisioningService
{
    public function isEnabled(): bool
    {
        return (bool) config('security.access_provisioning.enabled', true);
    }

    public function canBypassApproval(Authenticatable $actor): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (! method_exists($actor, 'hasAnyRole')) {
            return false;
        }

        return $actor->hasAnyRole(config('security.access_provisioning.bypass_roles', ['super-admin']));
    }

    public function requiresApproval(Authenticatable $actor): bool
    {
        if (! $this->isEnabled() || $this->canBypassApproval($actor)) {
            return false;
        }

        if (! method_exists($actor, 'hasAnyRole')) {
            return true;
        }

        return $actor->hasAnyRole(config('security.access_provisioning.approval_required_roles', ['admin']));
    }

    public function canApprove(Authenticatable $actor): bool
    {
        if (! method_exists($actor, 'can')) {
            return false;
        }

        if ($actor->can('access-requests.approve')) {
            return true;
        }

        return method_exists($actor, 'hasAnyRole')
            && $actor->hasAnyRole(config('security.access_provisioning.approver_roles', ['super-admin']));
    }

    public function submit(
        Authenticatable $requester,
        string $type,
        string $targetType,
        int $targetId,
        array $payload,
        ?string $justification = null
    ): AccessRequest {
        $request = AccessRequest::query()->create([
            'type' => $type,
            'status' => AccessRequest::STATUS_PENDING,
            'requester_id' => $requester->getAuthIdentifier(),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'justification' => $justification,
        ]);

        $this->notifyApprovers($request);

        app(SecurityEventLogger::class)->rbac('access_request.submitted', true, $requester, $requester, [
            'request_id' => $request->id,
            'type' => $type,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        return $request;
    }

    public function approve(AccessRequest $request, Authenticatable $reviewer, ?string $notes = null): void
    {
        if (! $request->isPending()) {
            return;
        }

        $this->apply($request);

        $request->update([
            'status' => AccessRequest::STATUS_APPROVED,
            'reviewer_id' => $reviewer->getAuthIdentifier(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        app(SecurityEventLogger::class)->rbac('access_request.approved', true, $reviewer, $reviewer, [
            'request_id' => $request->id,
        ]);
    }

    public function reject(AccessRequest $request, Authenticatable $reviewer, ?string $notes = null): void
    {
        if (! $request->isPending()) {
            return;
        }

        $request->update([
            'status' => AccessRequest::STATUS_REJECTED,
            'reviewer_id' => $reviewer->getAuthIdentifier(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        app(SecurityEventLogger::class)->rbac('access_request.rejected', true, $reviewer, $reviewer, [
            'request_id' => $request->id,
            'notes' => $notes,
        ]);
    }

    public function apply(AccessRequest $request): void
    {
        match ($request->type) {
            AccessRequest::TYPE_USER_UPDATE => $this->applyUserUpdate($request),
            AccessRequest::TYPE_ROLE_UPDATE => $this->applyRoleUpdate($request),
            default => null,
        };
    }

    protected function applyUserUpdate(AccessRequest $request): void
    {
        $model = config('security.user.model');
        $user = (new $model)->newQuery()->findOrFail($request->target_id);
        $payload = $request->payload;

        if (isset($payload['roles']) && method_exists($user, 'syncRoles')) {
            $user->syncRoles($payload['roles']);
        }

        $updates = array_intersect_key($payload, array_flip([
            'is_active', 'access_expires_at', 'must_change_password',
        ]));

        if ($updates !== []) {
            $user->update($updates);
        }
    }

    protected function applyRoleUpdate(AccessRequest $request): void
    {
        $role = Role::query()->findOrFail($request->target_id);

        if ($role->name === 'super-admin') {
            return;
        }

        $role->syncPermissions($request->payload['permissions'] ?? []);
    }

    protected function notifyApprovers(AccessRequest $request): void
    {
        $recipients = config('security.notifications.mail_to', []);

        if ($recipients === []) {
            return;
        }

        Notification::route('mail', $recipients)
            ->notify(new PendingAccessRequestNotification($request));
    }
}
