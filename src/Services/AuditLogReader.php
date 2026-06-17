<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Models\SecurityEvent;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Support\SecurityActorPresenter;

class AuditLogReader
{
    public function securityEvents(array $filters = []): LengthAwarePaginator
    {
        $query = SecurityEvent::query()->with('user')->latest();

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
        return SecurityEvent::query()->with('user')->find($id);
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
        $reviews = SecurityReview::query()->latest('performed_at')->paginate($this->perPage());
        $performers = $this->loadUsersByIds(
            $reviews->getCollection()->pluck('performed_by')->filter()->unique()->values()->all()
        );

        $reviews->getCollection()->transform(function (SecurityReview $review) use ($performers) {
            $review->setAttribute(
                'performer_label',
                SecurityActorPresenter::label($performers->get($review->performed_by))
            );

            return $review;
        });

        return $reviews;
    }

    protected function activityLog(array $filters): ?LengthAwarePaginator
    {
        if (! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return null;
        }

        $table = config('activitylog.table_name', 'activity_log');
        $userTable = config('security.user.table', 'users');
        $userModel = config('security.user.model');

        $query = DB::table($table)
            ->leftJoin($userTable, function ($join) use ($table, $userTable, $userModel) {
                $join->on($userTable.'.id', '=', $table.'.causer_id')
                    ->where($table.'.causer_type', '=', $userModel);
            })
            ->select($table.'.*', $userTable.'.name as causer_name', $userTable.'.email as causer_email')
            ->orderByDesc($table.'.created_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters, $table, $userTable) {
                $q->where($table.'.description', 'like', '%'.$filters['search'].'%')
                    ->orWhere($table.'.log_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere($userTable.'.name', 'like', '%'.$filters['search'].'%')
                    ->orWhere($userTable.'.email', 'like', '%'.$filters['search'].'%');
            });
        }

        $logs = $query->paginate($this->perPage())->withQueryString();
        $performers = $this->loadUsersByIds(
            collect($logs->items())->pluck('causer_id')->filter()->unique()->values()->all()
        );

        $logs->setCollection(
            collect($logs->items())->map(function ($row) use ($performers) {
                $performer = $performers->get($row->causer_id);
                $row->causer_label = $performer
                    ? SecurityActorPresenter::label($performer)
                    : ($row->causer_name ?: ($row->causer_email ?: '—'));

                return $row;
            })
        );

        return $logs;
    }

    protected function owenItAudits(array $filters): ?LengthAwarePaginator
    {
        if (! class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            return null;
        }

        $table = config('audit.drivers.database.table', 'audits');
        $userTable = config('security.user.table', 'users');
        $userModel = config('security.user.model');

        $query = DB::table($table)
            ->leftJoin($userTable, function ($join) use ($table, $userTable, $userModel) {
                $join->on($userTable.'.id', '=', $table.'.user_id')
                    ->where($table.'.user_type', '=', $userModel);
            })
            ->select($table.'.*', $userTable.'.name as causer_name', $userTable.'.email as causer_email')
            ->orderByDesc($table.'.created_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters, $table, $userTable) {
                $q->where($table.'.event', 'like', '%'.$filters['search'].'%')
                    ->orWhere($table.'.auditable_type', 'like', '%'.$filters['search'].'%')
                    ->orWhere($userTable.'.name', 'like', '%'.$filters['search'].'%')
                    ->orWhere($userTable.'.email', 'like', '%'.$filters['search'].'%');
            });
        }

        $logs = $query->paginate($this->perPage())->withQueryString();
        $performers = $this->loadUsersByIds(
            collect($logs->items())->pluck('user_id')->filter()->unique()->values()->all()
        );

        $logs->setCollection(
            collect($logs->items())->map(function ($row) use ($performers) {
                $performer = $performers->get($row->user_id);
                $row->causer_label = $performer
                    ? SecurityActorPresenter::label($performer)
                    : ($row->causer_name ?: ($row->causer_email ?: '—'));

                return $row;
            })
        );

        return $logs;
    }

    protected function loadUsersByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $model = config('security.user.model');

        return (new $model)->newQuery()->whereIn('id', $ids)->get()->keyBy('id');
    }

    protected function perPage(): int
    {
        return (int) config('security.admin.per_page', 25);
    }
}
