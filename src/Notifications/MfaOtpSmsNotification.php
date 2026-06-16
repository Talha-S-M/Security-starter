<?php

namespace Pitbphp\Security\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Pitbphp\Security\Notifications\Channels\MfaSmsChannel;

class MfaOtpSmsNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected string $sourceType = 'mfa_otp'
    ) {}

    public function via(object $notifiable): array
    {
        return [MfaSmsChannel::class];
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function toSms(object $notifiable): string
    {
        $minutes = (int) config('security.mfa.otp_expiry_minutes', 5);

        return "Your verification code is {$this->otp}. It expires in {$minutes} minutes.";
    }
}
