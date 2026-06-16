<?php

namespace Pitbphp\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Pitbphp\Security\Models\PasswordHistory;

class PasswordHistoryService
{
    public function isEnabledFor(Model $model): bool
    {
        return $model instanceof Authenticatable
            && method_exists($model, 'passwordHistories');
    }

    public function wasRecentlyUsed(Model $model, string $plainPassword): bool
    {
        if (! $this->isEnabledFor($model)) {
            return false;
        }

        $count = (int) config('security.password.history_count', 3);

        if ($count <= 0) {
            return false;
        }

        [$type, $id] = $this->resolveMorph($model);

        return PasswordHistory::query()
            ->where('passwordable_type', $type)
            ->where('passwordable_id', $id)
            ->latest()
            ->limit($count)
            ->get()
            ->contains(fn (PasswordHistory $history) => Hash::check($plainPassword, $history->password));
    }

    public function record(Model $model, string $hashedPassword): void
    {
        if (! $this->isEnabledFor($model)) {
            return;
        }

        [$type, $id] = $this->resolveMorph($model);

        PasswordHistory::query()->create([
            'passwordable_type' => $type,
            'passwordable_id' => $id,
            'password' => $hashedPassword,
        ]);

        $this->pruneExcess($model);
    }

    protected function pruneExcess(Model $model): void
    {
        $keep = (int) config('security.password.history_count', 3) + 1;
        [$type, $id] = $this->resolveMorph($model);

        $ids = PasswordHistory::query()
            ->where('passwordable_type', $type)
            ->where('passwordable_id', $id)
            ->latest()
            ->skip($keep)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            PasswordHistory::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @return array{0: string, 1: int|string}
     */
    protected function resolveMorph(Model $model): array
    {
        return [$model->getMorphClass(), $model->getKey()];
    }
}
