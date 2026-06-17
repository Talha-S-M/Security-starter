<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Support\SecurityActorPresenter;
use Pitbphp\Security\Support\SensitiveDataRedactor;

class SecurityEventLogger
{
    public function __construct(
        protected AuditLoggerInterface $auditLogger
    ) {}

    public function auth(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        $this->log('auth', $event, $success, $user, $user, $extra);
    }

    public function authorization(string $event, bool $success, ?Authenticatable $user = null, array $extra = []): void
    {
        $this->log('authorization', $event, $success, $user, $user, $extra);
    }

    public function rbac(
        string $event,
        bool $success,
        Authenticatable|Model|null $subject = null,
        ?Authenticatable $causer = null,
        array $extra = []
    ): void {
        $this->writeEvent($event, $success, $subject, $causer, $extra, 'rbac');
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

        $properties = array_merge(
            $this->enrichActors(null, $performer),
            [
                'review_id' => $review->id,
                'notes' => $notes,
                'metadata' => $metadata,
            ]
        );

        $this->auditLogger->log('security.review.'.$type, $properties, null, $performer instanceof Model ? $performer : null);

        return $review;
    }

    public function pruneEvents(\DateTimeInterface $before): int
    {
        return SecurityEvent::query()
            ->where('created_at', '<', $before)
            ->delete();
    }

    protected function log(
        string $category,
        string $event,
        bool $success,
        ?Authenticatable $subject,
        ?Authenticatable $causer,
        array $extra
    ): void {
        $this->writeEvent($event, $success, $subject, $causer, $extra, $category);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function writeEvent(
        string $event,
        bool $success,
        Authenticatable|Model|null $subject,
        ?Authenticatable $causer,
        array $context,
        string $category,
    ): void {
        $properties = $this->buildProperties($category, $success, $subject, $causer, $context);

        if ($category === 'rbac') {
            $this->auditLogger->log(
                $event,
                $properties,
                $subject instanceof Model ? $subject : null,
                $causer instanceof Model ? $causer : null,
            );

            return;
        }

        $request = request();

        SecurityEvent::query()->create([
            'user_id' => $causer?->getAuthIdentifier() ?? ($subject instanceof Authenticatable ? $subject->getAuthIdentifier() : null),
            'event_type' => $event,
            'success' => $success,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildProperties(
        string $category,
        bool $success,
        Authenticatable|Model|null $subject,
        ?Authenticatable $causer,
        array $context,
    ): array {
        $request = request();

        return array_merge([
            'category' => $category,
            'success' => $success,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'origination' => $request?->ip(),
        ], $this->enrichActors($subject, $causer), $this->sanitize($context));
    }

    /**
     * @return array<string, mixed>
     */
    protected function enrichActors(Authenticatable|Model|null $subject, ?Authenticatable $causer): array
    {
        $data = [];

        if ($causerSnapshot = SecurityActorPresenter::snapshot($causer)) {
            $data['causer'] = $causerSnapshot;
        }

        if ($subjectSnapshot = SecurityActorPresenter::snapshot($subject)) {
            $data['subject'] = $subjectSnapshot;
        }

        return $data;
    }

    protected function sanitize(array $data): array
    {
        return SensitiveDataRedactor::redact($data);
    }
}
