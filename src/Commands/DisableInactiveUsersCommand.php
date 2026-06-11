<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Services\SecurityNotifier;

class DisableInactiveUsersCommand extends Command
{
    protected $signature = 'security:disable-inactive-users
                            {--dry-run : List accounts without disabling}
                            {--notify : Email reviewers about inactive accounts}';

    protected $description = 'Disable user accounts inactive beyond the configured threshold';

    public function handle(SecurityEventLogger $logger, SecurityNotifier $notifier): int
    {
        $days = (int) config('security.inactive_accounts.disable_after_days', 60);
        $table = config('security.user.table', 'users');
        $cutoff = now()->subDays($days);

        if (! $this->hasColumn($table, 'last_login_at') && ! $this->hasColumn($table, 'is_active')) {
            $this->error('Users table is missing required columns. Run migrations first.');

            return self::FAILURE;
        }

        $query = DB::table($table);

        if ($this->hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }

        if ($this->hasColumn($table, 'last_login_at')) {
            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $cutoff);
            });
        }

        $users = $query->get(['id', 'last_login_at']);
        $ids = $users->pluck('id')->all();

        if ($ids === []) {
            $this->info('No inactive accounts found.');

            return self::SUCCESS;
        }

        if ($this->option('notify') && config('security.notifications.inactive_accounts.enabled')) {
            $notifier->notifyInactiveAccountsReview($ids);
            $this->info('Inactive accounts notification sent.');
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — would disable: '.implode(', ', $ids));

            return self::SUCCESS;
        }

        if ($this->hasColumn($table, 'is_active')) {
            DB::table($table)->whereIn('id', $ids)->update(['is_active' => false]);
        }

        foreach ($ids as $id) {
            $logger->auth('account.disabled_inactivity', true, null, ['user_id' => $id, 'days' => $days]);
        }

        $this->info('Disabled '.count($ids).' inactive account(s).');

        return self::SUCCESS;
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return in_array($column, DB::getSchemaBuilder()->getColumnListing($table), true);
    }
}
