<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Support\SecurityRoutes;

class PendingAccessRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected AccessRequest $request
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PITB Security: Access change awaiting approval')
            ->line("A new {$this->request->type} request is pending approval.")
            ->line("Request ID: {$this->request->id}")
            ->line('Review it in the security admin panel:')
            ->action('Review requests', url(SecurityRoutes::adminPath('partials/access-requests')));
    }
}
