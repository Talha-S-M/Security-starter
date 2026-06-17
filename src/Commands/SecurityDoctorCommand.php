<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Pitbphp\Security\Support\AuditingMigrationPublisher;
use Pitbphp\Security\Support\SecurityRoutes;
use Pitbphp\Security\Support\VendorConfigAligner;

class SecurityDoctorCommand extends Command
{
    protected $signature = 'security:doctor';

    protected $description = 'Verify PITB Security installation health and highlight fixes';

    public function handle(): int
    {
        $failed = false;

        $this->line('Running PITB Security checks...');
        $this->newLine();

        $failed = ! $this->checkRoute(SecurityRoutes::name('home'), 'Main security route registered') || $failed;
        $failed = ! $this->checkRoute(SecurityRoutes::name('mfa.verify'), 'MFA route registered') || $failed;
        $failed = ! $this->checkClass(\Spatie\Permission\Models\Role::class, 'Spatie Permission installed') || $failed;

        $driver = (string) config('security.auditing.driver', 'activitylog');
        if ($driver === 'activitylog') {
            $failed = ! $this->checkClass(\Spatie\Activitylog\Models\Activity::class, 'Activitylog package installed') || $failed;
            $table = AuditingMigrationPublisher::requiredTable($driver);
            if ($table) {
                $failed = ! $this->checkTable($table, "Activitylog table [{$table}] migrated") || $failed;
            }
        } elseif ($driver === 'auditing') {
            $failed = ! $this->checkClass(\OwenIt\Auditing\Models\Audit::class, 'Owen-It auditing package installed') || $failed;
            $table = AuditingMigrationPublisher::requiredTable($driver);
            if ($table) {
                $failed = ! $this->checkTable($table, "Auditing table [{$table}] migrated") || $failed;
            }
        }

        $failed = ! $this->checkTable('security_events', 'Security events table migrated') || $failed;
        $failed = ! $this->checkTable('password_histories', 'Password history table migrated') || $failed;
        $failed = ! $this->checkTable('security_reviews', 'Security reviews table migrated') || $failed;
        $failed = ! $this->checkTable('access_requests', 'Access requests table migrated') || $failed;

        $mailTo = array_filter((array) config('security.notifications.mail_to', []));
        $this->check(! empty($mailTo), 'SECURITY_MAIL_TO configured');

        $configIssues = VendorConfigAligner::diagnose();
        if ($configIssues !== []) {
            $this->newLine();
            $this->warn('Legacy vendor env keys detected (safe but redundant due runtime alignment):');
            foreach ($configIssues as $issue) {
                $this->line('  - '.$issue);
            }
            $this->line('  Recommendation: remove old vendor env keys (CAPTCHA_DISABLE, ACTIVITY_LOGGER_ENABLED, AUDITING_ENABLED, PERMISSION_GUARD), keep SECURITY_* only, then run `php artisan config:clear`.');
        } else {
            $this->check(true, 'Vendor configs aligned with config/security.php');
        }

        $this->newLine();
        if ($failed) {
            $this->error('Security doctor found issues. Run `php artisan security:install` or apply the suggested fixes.');
            if ($driver === 'activitylog') {
                $this->line('Missing activity_log? Run: php artisan security:publish-vendor-config --driver=activitylog && php artisan migrate');
            }
            return self::FAILURE;
        }

        $this->info('All critical security package checks passed.');
        return self::SUCCESS;
    }

    protected function checkRoute(string $name, string $label): bool
    {
        return $this->check(Route::has($name), $label);
    }

    protected function checkClass(string $class, string $label): bool
    {
        return $this->check(class_exists($class), $label);
    }

    protected function checkTable(string $table, string $label): bool
    {
        try {
            return $this->check(Schema::hasTable($table), $label);
        } catch (\Throwable $e) {
            return $this->check(false, $label.' (database connection issue)');
        }
    }

    protected function check(bool $condition, string $label): bool
    {
        $this->line(($condition ? '[OK] ' : '[FAIL] ').$label);
        return $condition;
    }
}
