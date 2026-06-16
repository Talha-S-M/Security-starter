<?php

namespace Pitbphp\Security\Services;

use Illuminate\Support\Facades\Log;
use Pitbphp\Security\Contracts\SmsGatewayInterface;

class LogSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message, array $options = []): array
    {
        Log::info('PITB Security SMS (log driver)', [
            'phone' => $phone,
            'message' => $message,
            'options' => $options,
        ]);

        return ['status' => true, 'message' => 'SMS logged locally.'];
    }
}
