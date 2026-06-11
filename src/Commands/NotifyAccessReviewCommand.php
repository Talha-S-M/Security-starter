<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Services\SecurityNotifier;

class NotifyAccessReviewCommand extends Command
{
    protected $signature = 'security:notify-access-review';

    protected $description = 'Email responsible staff that a bi-annual access review is due';

    public function handle(SecurityNotifier $notifier): int
    {
        if (! config('security.notifications.access_review.enabled')) {
            $this->info('Access review notifications are disabled.');

            return self::SUCCESS;
        }

        $notifier->notifyAccessReviewDue();
        $this->info('Access review reminder sent.');

        return self::SUCCESS;
    }
}
