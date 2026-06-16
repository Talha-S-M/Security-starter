<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Support\SensitiveDataRedactor;

class SecurityEventLogger
{
    public function __construct(
        protected AuditLoggerInterface $auditLogger
    ) {}

    public function auth(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        $this->log('auth', $event, $success, $user, $extra);
    }

    public function authorization(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        $this->log('authorization', $event, $success, $user, $extra);
    }

    public function rbac(string $event, bool $success, ?Authenticatable $subject = null, ?Authenticatable $causer = null, array $extra = []): void
    {
        $request = request();

        $properties = array_merge([
            'success' => $success,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'origination' => $request?->ip(),
        ], $this->sanitize($extra));

        SecurityEvent::query()->create([
            'user_id' => $causer?->getAuthIdentifier() ?? $subject?->getAuthIdentifier(),
            'event_type' => $event,
            'success' => $success,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties,
        ]);

        $auditSubject = $subject instanceof Model ? $subject : null;
        $auditCauser = $causer instanceof Model ? $causer : null;

        $this->auditLogger->log($event, $properties, $auditSubject, $auditCauser);
    }

    public function recordReview(
        string $type,
        Authenticatable $performer,
        ?string $notes = null,
        array $metadata = []
    ): \Pitbphp\Security\Models\SecurityReview {
        $review = \Pitbphp\Security\Models\SecurityReview::query()->create([
            'type' => $type,
            'performed_by' => $performer->getAuthIdentifier(),
            'notes' => $notes,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);

        $this->auditLogger->log('security.review.'.$type, [
            'review_id' => $review->id,
            'notes' => $notes,
            'metadata' => $metadata,
        ], null, $performer);

        return $review;
    }

    public function pruneEvents(\DateTimeInterface $before): int
    {
        return SecurityEvent::query()
            ->where('created_at', '<', $before)
            ->delete();
    }

    protected function log(string $category, string $event, bool $success, ?Authenticatable $user, array $extra): void
    {
        $request = request();

        $properties = array_merge([
            'category' => $category,
            'success' => $success,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'origination' => $request?->ip(),
        ], $this->sanitize($extra));

        SecurityEvent::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'event_type' => $event,
            'success' => $success,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties,
        ]);

        $subject = $user instanceof Model ? $user : null;

        $this->auditLogger->log($event, $properties, $subject, $subject);
    }

    protected function sanitize(array $data): array
    {
        return SensitiveDataRedactor::redact($data);
    }
}
