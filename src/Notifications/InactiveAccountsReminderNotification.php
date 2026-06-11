<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InactiveAccountsReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected array $userIds
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->userIds);
        $days = (int) config('security.inactive_accounts.disable_after_days', 60);

        return (new MailMessage)
            ->subject('PITB Security: Inactive Accounts Review')
            ->line("{$count} user account(s) are inactive beyond {$days} days and may need disabling.")
            ->line('User IDs: '.implode(', ', $this->userIds))
            ->line('After review, disable accounts with:')
            ->line('php artisan security:disable-inactive-users')
            ->line('Record the review with:')
            ->line('php artisan security:record-inactive-review --notes="Your review summary"');
    }
}
