<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Notifications\PendingAccessRequestNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use Pitbphp\Security\Support\SecurityLog;
use Pitbphp\Security\Support\SecurityTier;

class AccessProvisioningService
{
    public function isEnabled(): bool
    {
        return SecurityTier::accessProvisioningEnabled();
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
            && $actor->hasAnyRole(config('security.access_provisioning.approver_roles', ['super-admin', 'admin']));
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<string, mixed>
     */
    public function buildUserPayload(string $name, string $email, string $hashedPassword, array $roles = []): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'roles' => $roles !== [] ? $roles : [config('security.permissions.default_user_role', 'user')],
            'is_active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
        ];
    }

    public function createUser(array $payload, ?Authenticatable $causer = null): Model
    {
        $model = config('security.user.model');
        $causer ??= Auth::user();

        $attributes = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'is_active' => $payload['is_active'] ?? true,
            'must_change_password' => $payload['must_change_password'] ?? true,
            'password_changed_at' => $payload['password_changed_at'] ?? null,
            'mfa_configured_at' => null,
        ];

        foreach (['phone', 'mfa_email'] as $field) {
            if (array_key_exists($field, $payload)) {
                $attributes[$field] = $payload[$field];
            }
        }

        $user = (new $model)->newQuery()->create($attributes);

        if (method_exists($user, 'syncMfaMethods')) {
            $user->syncMfaMethods();
            $user->save();
        }

        if (isset($payload['roles']) && method_exists($user, 'syncRoles')) {
            $user->syncRoles($payload['roles']);
        }

        SecurityLog::rbac('user.created', true, $user, $causer, [
            'target' => [
                'id' => $user->getKey(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : ($payload['roles'] ?? []),
            ],
            'roles' => $payload['roles'] ?? [],
        ]);

        return $user;
    }

    public function updateUser(Model $user, array $payload, ?Authenticatable $causer = null): void
    {
        $causer ??= Auth::user();
        $changes = [];

        if (isset($payload['roles']) && method_exists($user, 'syncRoles')) {
            $before = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];
            $after = array_values($payload['roles']);
            sort($before);
            sort($after);

            if ($before !== $after) {
                $user->syncRoles($payload['roles']);
                $changes['roles'] = [
                    'from' => $before,
                    'to' => $after,
                ];
            }
        }

        $scalarFields = ['is_active', 'access_expires_at', 'must_change_password', 'email', 'name', 'phone', 'mfa_email'];

        foreach ($scalarFields as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $new = $payload[$field];
            $old = $user->getAttribute($field);

            if ($field === 'access_expires_at') {
                $old = $old ? \Illuminate\Support\Carbon::parse($old)->toDateString() : null;
                $new = $new ? \Illuminate\Support\Carbon::parse($new)->toDateString() : null;
            }

            if ($old == $new) {
                continue;
            }

            $changes[$field] = [
                'from' => $old,
                'to' => $new,
            ];
            $user->{$field} = $new;
        }

        if (isset($payload['password'])) {
            $changes['password'] = [
                'from' => '********',
                'to' => '********',
            ];
            $user->password = $payload['password'];

            if (array_key_exists('password_changed_at', $payload)) {
                $user->password_changed_at = $payload['password_changed_at'];
            }
        }

        $mfaContactsChanged = array_key_exists('phone', $changes)
            || array_key_exists('mfa_email', $changes)
            || array_key_exists('email', $changes);

        if ($mfaContactsChanged && method_exists($user, 'syncMfaMethods')) {
            $beforeMethods = $user->mfa_methods ?? [];
            $user->syncMfaMethods();

            if (($user->mfa_methods ?? []) != $beforeMethods) {
                $changes['mfa_methods'] = [
                    'from' => $beforeMethods,
                    'to' => $user->mfa_methods ?? [],
                ];
            }
        }

        if (! $user->isDirty() && $changes === []) {
            return;
        }

        if ($user->isDirty()) {
            $user->save();
        }

        if ($changes === []) {
            return;
        }

        SecurityLog::rbac('user.updated', true, $user, $causer, [
            'target' => [
                'id' => $user->getKey(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [],
            ],
            'changes' => $changes,
        ]);
    }

    public function updateRolePermissions(Role $role, array $permissions, ?Authenticatable $causer = null): void
    {
        $causer ??= Auth::user();
        $before = $role->permissions()->pluck('name')->values()->all();

        $role->syncPermissions($permissions);

        SecurityLog::rbac('role.permissions.updated', true, $role, $causer, [
            'role' => $role->name,
            'permissions_from' => $before,
            'permissions_to' => array_values($permissions),
        ]);
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

        SecurityLog::rbac('access_request.submitted', true, $requester, $requester, [
            'request_id' => $request->id,
            'type' => $type,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target' => $this->payloadTargetSnapshot($payload),
            'justification' => $justification,
        ]);

        return $request;
    }

    public function registerVerifiedUser(array $payload): Model
    {
        $user = $this->createUser($payload);

        SecurityLog::auth('registration.completed', true, $user, [
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ]);

        return $user;
    }

    public function submitRegistration(array $payload): AccessRequest
    {
        $request = AccessRequest::query()->create([
            'type' => AccessRequest::TYPE_USER_REGISTRATION,
            'status' => AccessRequest::STATUS_PENDING,
            'requester_id' => null,
            'target_type' => config('security.user.model'),
            'target_id' => 0,
            'payload' => $payload,
            'justification' => 'Public registration request',
        ]);

        $this->notifyApprovers($request);

        SecurityLog::auth('registration.submitted', true, null, [
            'request_id' => $request->id,
            'target' => $this->payloadTargetSnapshot($payload),
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
        ]);

        return $request;
    }

    public function hasPendingRegistration(string $email): bool
    {
        return AccessRequest::query()
            ->where('type', AccessRequest::TYPE_USER_REGISTRATION)
            ->where('status', AccessRequest::STATUS_PENDING)
            ->where('payload->email', $email)
            ->exists();
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

        SecurityLog::rbac('access_request.approved', true, $reviewer, $reviewer, [
            'request_id' => $request->id,
            'type' => $request->type,
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
            'target' => $this->payloadTargetSnapshot($request->payload ?? []),
            'review_notes' => $notes,
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

        SecurityLog::rbac('access_request.rejected', true, $reviewer, $reviewer, [
            'request_id' => $request->id,
            'type' => $request->type,
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
            'target' => $this->payloadTargetSnapshot($request->payload ?? []),
            'notes' => $notes,
        ]);
    }

    public function apply(AccessRequest $request): void
    {
        match ($request->type) {
            AccessRequest::TYPE_USER_CREATE,
            AccessRequest::TYPE_USER_REGISTRATION => $this->applyUserCreate($request),
            AccessRequest::TYPE_USER_UPDATE => $this->applyUserUpdate($request),
            AccessRequest::TYPE_ROLE_UPDATE => $this->applyRoleUpdate($request),
            default => null,
        };
    }

    protected function applyUserCreate(AccessRequest $request): void
    {
        $user = $this->createUser($request->payload, Auth::user());

        $request->update([
            'target_id' => (int) $user->getKey(),
        ]);
    }

    protected function applyUserUpdate(AccessRequest $request): void
    {
        $model = config('security.user.model');
        $user = (new $model)->newQuery()->findOrFail($request->target_id);

        $this->updateUser($user, $request->payload, Auth::user());
    }

    protected function applyRoleUpdate(AccessRequest $request): void
    {
        $role = Role::query()->findOrFail($request->target_id);

        if ($role->name === 'super-admin') {
            return;
        }

        $this->updateRolePermissions($role, $request->payload['permissions'] ?? [], Auth::user());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function payloadTargetSnapshot(array $payload): ?array
    {
        if ($payload === []) {
            return null;
        }

        return [
            'id' => $payload['target_user_id'] ?? null,
            'name' => $payload['name'] ?? null,
            'email' => $payload['email'] ?? null,
            'roles' => $payload['roles'] ?? [],
            'role' => $payload['role'] ?? null,
        ];
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
