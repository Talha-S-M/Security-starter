<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LogReviewReminderNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PITB Security: Daily Log Review Required')
            ->line('Please review security audit logs for anomalies or suspicious events.')
            ->line('Focus on: failed authentications, admin activity, and sensitive data access.')
            ->line('After completing the review, record it with:')
            ->line('php artisan security:record-log-review --notes="Your review summary"');
    }
}
