<?php

namespace Pitbphp\Security\Services;

use Illuminate\Support\Facades\Notification;
use Pitbphp\Security\Notifications\AccessReviewReminderNotification;
use Pitbphp\Security\Notifications\InactiveAccountsReminderNotification;
use Pitbphp\Security\Notifications\LogReviewReminderNotification;

class SecurityNotifier
{
    public function recipients(): array
    {
        return config('security.notifications.mail_to', []);
    }

    public function notifyAccessReviewDue(): void
    {
        $this->send(new AccessReviewReminderNotification());
    }

    public function notifyLogReviewDue(): void
    {
        $this->send(new LogReviewReminderNotification());
    }

    public function notifyInactiveAccountsReview(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $this->send(new InactiveAccountsReminderNotification($userIds));
    }

    protected function send(object $notification): void
    {
        $recipients = $this->recipients();

        if ($recipients === []) {
            return;
        }

        Notification::route('mail', $recipients)->notify($notification);
    }
}
