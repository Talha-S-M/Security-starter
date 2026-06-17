<?php

namespace Pitbphp\Security\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Models\SecurityEvent;

class SecurityActorPresenter
{
    public static function label(?Authenticatable $user, bool $withRoles = true): string
    {
        if (! $user) {
            return '—';
        }

        return self::format(
            $user->getAuthIdentifier(),
            $user->name ?? null,
            $user->email ?? null,
            $withRoles && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : []
        );
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     */
    public static function fromSnapshot(?array $snapshot): string
    {
        if (! $snapshot) {
            return '—';
        }

        return self::format(
            $snapshot['id'] ?? null,
            $snapshot['name'] ?? null,
            $snapshot['email'] ?? null,
            $snapshot['roles'] ?? []
        );
    }

    public static function causerForEvent(SecurityEvent $event): string
    {
        $properties = $event->properties ?? [];

        if (! empty($properties['causer'])) {
            return self::fromSnapshot($properties['causer']);
        }

        if ($event->relationLoaded('user') && $event->user instanceof Authenticatable) {
            return self::label($event->user);
        }

        if ($event->user_id) {
            return 'User #'.$event->user_id;
        }

        return '—';
    }

    public static function subjectForEvent(SecurityEvent $event): string
    {
        $properties = $event->properties ?? [];

        if (! empty($properties['subject'])) {
            return self::fromSnapshot($properties['subject']);
        }

        if (! empty($properties['target'])) {
            return self::fromSnapshot($properties['target']);
        }

        if (! empty($properties['name']) || ! empty($properties['email'])) {
            return self::format(
                $properties['target_user_id'] ?? $properties['subject_id'] ?? null,
                $properties['name'] ?? null,
                $properties['email'] ?? null,
                $properties['roles'] ?? []
            );
        }

        if (! empty($properties['role'])) {
            return (string) $properties['role'];
        }

        if (! empty($properties['target_user_id'])) {
            return 'User #'.$properties['target_user_id'];
        }

        return '—';
    }

    /**
     * @param  array<int, string>  $roles
     */
    public static function format(int|string|null $id, ?string $name, ?string $email, array $roles = []): string
    {
        $display = $name ?: $email;

        if (! $display && $id !== null) {
            $display = 'User #'.$id;
        }

        if (! $display) {
            return '—';
        }

        $roles = array_values(array_filter($roles));

        if ($roles === []) {
            return $display;
        }

        return $display.' ('.implode(', ', $roles).')';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function snapshot(Authenticatable|Model|null $actor): ?array
    {
        if (! $actor) {
            return null;
        }

        $roles = [];

        if ($actor instanceof Authenticatable && method_exists($actor, 'getRoleNames')) {
            $roles = $actor->getRoleNames()->values()->all();
        }

        $name = $actor->name ?? $actor->email ?? null;

        if (! $name && $actor instanceof Model) {
            $name = class_basename($actor).' #'.($actor->getKey() ?? '?');
        }

        return [
            'id' => $actor instanceof Authenticatable ? $actor->getAuthIdentifier() : $actor->getKey(),
            'name' => $name,
            'email' => $actor->email ?? null,
            'roles' => $roles,
            'type' => class_basename($actor),
        ];
    }
}
