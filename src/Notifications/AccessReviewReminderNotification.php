<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessReviewReminderNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $months = (int) config('security.notifications.access_review.interval_months', 6);

        return (new MailMessage)
            ->subject('PITB Security: Access Review Required')
            ->line("A bi-annual access review is due (every {$months} months).")
            ->line('Please validate that user and privileged access remains appropriate.')
            ->line('After completing the review, record it with:')
            ->line('php artisan security:record-access-review --notes="Your review summary"');
    }
}
