<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Models\SecurityReview;

class SecurityEventLogger
{
    public function __construct(
        protected AuditLoggerInterface $auditLogger
    ) {}

    public function auth(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        $request = request();

        $properties = array_merge([
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

        $this->auditLogger->log($event, $properties, $user, $user);
    }

    public function recordReview(
        string $type,
        Authenticatable $performer,
        ?string $notes = null,
        array $metadata = []
    ): SecurityReview {
        $review = SecurityReview::query()->create([
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

    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), ['password', 'otp', 'token', 'secret'], true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}
