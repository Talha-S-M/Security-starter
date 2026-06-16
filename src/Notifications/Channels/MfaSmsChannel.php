<?php

namespace Pitbphp\Security\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Pitbphp\Security\Contracts\SmsGatewayInterface;

class MfaSmsChannel
{
    public function __construct(
        protected SmsGatewayInterface $gateway
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->phone ?? null;

        if (! $phone) {
            return;
        }

        $identifier = $notifiable->cnic
            ?? $notifiable->email
            ?? (string) ($notifiable->getAuthIdentifier() ?? '');

        $this->gateway->send($phone, $notification->toSms($notifiable), [
            'identifier' => $identifier,
            'source_type' => method_exists($notification, 'sourceType')
                ? $notification->sourceType()
                : 'mfa_otp',
            'language' => config('security.sms.default_language', 'urdu'),
            'validate_rate_limit' => true,
        ]);
    }
}
