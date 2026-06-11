<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Services\SecurityNotifier;

class NotifyLogReviewCommand extends Command
{
    protected $signature = 'security:notify-log-review';

    protected $description = 'Email responsible staff that daily log review is due';

    public function handle(SecurityNotifier $notifier): int
    {
        if (! config('security.notifications.log_review.enabled')) {
            $this->info('Log review notifications are disabled.');

            return self::SUCCESS;
        }

        $notifier->notifyLogReviewDue();
        $this->info('Log review reminder sent.');

        return self::SUCCESS;
    }
}
