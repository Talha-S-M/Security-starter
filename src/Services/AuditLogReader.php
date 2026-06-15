<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Models\SecurityReview;

class AuditLogReader
{
    public function securityEvents(array $filters = []): LengthAwarePaginator
    {
        $query = SecurityEvent::query()->latest();

        if (! empty($filters['event_type'])) {
            $query->where('event_type', 'like', '%'.$filters['event_type'].'%');
        }

        if (isset($filters['success']) && $filters['success'] !== '') {
            $query->where('success', (bool) $filters['success']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($this->perPage())->withQueryString();
    }

    public function securityEvent(int $id): ?SecurityEvent
    {
        return SecurityEvent::query()->find($id);
    }

    public function auditTrail(array $filters = []): ?LengthAwarePaginator
    {
        $driver = config('security.auditing.driver', 'activitylog');

        return match ($driver) {
            'activitylog' => $this->activityLog($filters),
            'auditing' => $this->owenItAudits($filters),
            default => null,
        };
    }

    public function auditDriverLabel(): string
    {
        return match (config('security.auditing.driver', 'activitylog')) {
            'activitylog' => 'Activity Log',
            'auditing' => 'Owen-It Audits',
            default => 'None',
        };
    }

    public function reviews(): LengthAwarePaginator
    {
        return SecurityReview::query()->latest('performed_at')->paginate($this->perPage());
    }

    protected function activityLog(array $filters): ?LengthAwarePaginator
    {
        if (! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return null;
        }

        $table = config('activitylog.table_name', 'activity_log');
        $query = DB::table($table)->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters, $table) {
                $q->where('description', 'like', '%'.$filters['search'].'%')
                    ->orWhere('log_name', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->paginate($this->perPage())->withQueryString();
    }

    protected function owenItAudits(array $filters): ?LengthAwarePaginator
    {
        if (! class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            return null;
        }

        $table = config('audit.drivers.database.table', 'audits');
        $query = DB::table($table)->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('event', 'like', '%'.$filters['search'].'%')
                    ->orWhere('auditable_type', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->paginate($this->perPage())->withQueryString();
    }

    protected function perPage(): int
    {
        return (int) config('security.admin.per_page', 25);
    }
}
