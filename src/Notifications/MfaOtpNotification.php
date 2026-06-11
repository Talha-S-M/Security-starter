<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MfaOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('security.mfa.otp_expiry_minutes', 5);

        return (new MailMessage)
            ->subject('Your verification code')
            ->line("Your one-time verification code is: {$this->otp}")
            ->line("This code expires in {$minutes} minutes and can only be used once.");
    }
}
