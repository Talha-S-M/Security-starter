<?php

namespace Pitbphp\Security\Support;

use Illuminate\Support\Carbon;

class AuditChangeSummarizer
{
    public static function forActivityLog(object $row): ?string
    {
        $properties = self::decode($row->properties ?? null);

        if ($properties === []) {
            return null;
        }

        return self::fromEventProperties((string) ($row->description ?? ''), $properties);
    }

    public static function forOwenItAudit(object $row): ?string
    {
        $old = self::decode($row->old_values ?? null);
        $new = self::decode($row->new_values ?? null);
        $event = (string) ($row->event ?? '');

        if ($new !== [] && isset($new['changes'])) {
            return self::summarizeChanges((array) $new['changes']);
        }

        if ($new !== [] && ($summary = self::fromEventProperties($event, $new)) !== null) {
            return $summary;
        }

        if ($old !== [] || $new !== []) {
            return self::summarizeAttributeDiff($old, $new);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function fromEventProperties(string $event, array $properties): ?string
    {
        return match ($event) {
            'user.updated' => isset($properties['changes'])
                ? self::summarizeChanges((array) $properties['changes'])
                : null,
            'user.created' => self::summarizeUserCreated($properties),
            'role.permissions.updated' => self::summarizePermissionChange($properties),
            'rbac.role.attached', 'rbac.role.detached' => self::summarizeRbacAttach($event, $properties, 'Role'),
            'rbac.permission.attached', 'rbac.permission.detached' => self::summarizeRbacAttach($event, $properties, 'Permission'),
            'access_request.submitted', 'access_request.approved', 'access_request.rejected' => self::summarizeAccessRequest($properties),
            default => self::summarizeGenericProperties($properties),
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function summarizeChanges(array $changes): ?string
    {
        $parts = [];

        foreach ($changes as $field => $change) {
            if (is_array($change) && array_key_exists('from', $change) && array_key_exists('to', $change)) {
                $parts[] = self::fieldLabel((string) $field).': '
                    .self::formatFieldValue((string) $field, $change['from'])
                    .' → '
                    .self::formatFieldValue((string) $field, $change['to']);

                continue;
            }

            if ($field === 'roles' && is_array($change)) {
                $parts[] = self::fieldLabel('roles').': '
                    .self::formatList($change['from'] ?? [])
                    .' → '
                    .self::formatList($change['to'] ?? []);
            }
        }

        return $parts === [] ? null : implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    protected static function summarizeAttributeDiff(array $old, array $new): ?string
    {
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $parts = [];

        foreach ($keys as $key) {
            $from = $old[$key] ?? null;
            $to = $new[$key] ?? null;

            if ($from == $to) {
                continue;
            }

            $parts[] = self::fieldLabel((string) $key).': '
                .self::formatFieldValue((string) $key, $from)
                .' → '
                .self::formatFieldValue((string) $key, $to);
        }

        return $parts === [] ? null : implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function summarizeUserCreated(array $properties): ?string
    {
        $parts = [];
        $target = (array) ($properties['target'] ?? []);

        if (! empty($target['email'])) {
            $parts[] = 'Email: '.$target['email'];
        }

        $roles = $target['roles'] ?? $properties['roles'] ?? [];

        if ($roles !== []) {
            $parts[] = 'Roles: '.self::formatList($roles);
        }

        return $parts === [] ? null : implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function summarizePermissionChange(array $properties): ?string
    {
        $role = $properties['role'] ?? null;

        if (! isset($properties['permissions_from'], $properties['permissions_to'])) {
            return $role ? 'Role: '.$role : null;
        }

        $from = self::formatList((array) $properties['permissions_from']);
        $to = self::formatList((array) $properties['permissions_to']);

        return ($role ? 'Role: '.$role.'; ' : '').'Permissions: '.$from.' → '.$to;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function summarizeRbacAttach(string $event, array $properties, string $label): ?string
    {
        $action = str_contains($event, 'detached') ? 'removed' : 'added';
        $name = $properties['name'] ?? null;

        return $name ? $label.' '.$action.': '.$name : null;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function summarizeAccessRequest(array $properties): ?string
    {
        $parts = [];

        if (! empty($properties['type'])) {
            $parts[] = 'Type: '.str_replace('_', ' ', (string) $properties['type']);
        }

        $target = (array) ($properties['target'] ?? []);

        if (! empty($target['email'])) {
            $parts[] = 'Email: '.$target['email'];
        }

        if (! empty($target['roles'])) {
            $parts[] = 'Roles: '.self::formatList((array) $target['roles']);
        }

        if (! empty($properties['request_id'])) {
            $parts[] = 'Request #'.$properties['request_id'];
        }

        return $parts === [] ? null : implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function summarizeGenericProperties(array $properties): ?string
    {
        unset($properties['category'], $properties['success'], $properties['ip'], $properties['user_agent'], $properties['origination'], $properties['causer'], $properties['subject']);

        if ($properties === []) {
            return null;
        }

        if (isset($properties['changes']) && is_array($properties['changes'])) {
            return self::summarizeChanges($properties['changes']);
        }

        $parts = [];

        foreach ($properties as $key => $value) {
            if (is_scalar($value) && $value !== '') {
                $parts[] = self::fieldLabel((string) $key).': '.$value;
            }
        }

        return $parts === [] ? null : implode('; ', array_slice($parts, 0, 4));
    }

    protected static function fieldLabel(string $field): string
    {
        return match ($field) {
            'roles' => 'Roles',
            'is_active' => 'Account active',
            'access_expires_at' => 'Access expires',
            'must_change_password' => 'Force password change',
            'email' => 'Email',
            'name' => 'Name',
            'phone' => 'Phone',
            'mfa_email' => 'MFA email',
            'mfa_methods' => 'MFA methods',
            'contact', 'mobile' => 'Contact',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    protected static function formatFieldValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'is_active' || $field === 'must_change_password') {
            return $value ? 'Yes' : 'No';
        }

        if ($field === 'roles') {
            return self::formatList(is_array($value) ? $value : [$value]);
        }

        if ($field === 'access_expires_at') {
            try {
                return Carbon::parse((string) $value)->format('j M Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return self::formatList($value);
        }

        return (string) $value;
    }

  /**
     * @param  array<int|string, mixed>  $values
     */
    protected static function formatList(array $values): string
    {
        $values = array_values(array_filter($values, fn ($value) => $value !== null && $value !== ''));

        if ($values === []) {
            return '—';
        }

        $formatted = array_map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $values);
        $text = implode(', ', $formatted);

        return strlen($text) > 80 ? substr($text, 0, 77).'…' : $text;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
