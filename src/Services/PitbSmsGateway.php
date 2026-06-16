<?php

namespace Pitbphp\Security\Services;

use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Contracts\SmsGatewayInterface;

class PitbSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message, array $options = []): array
    {
        $cnic = (string) ($options['identifier'] ?? '');
        $sourceType = $options['source_type'] ?? 'mfa_otp';
        $language = $options['language'] ?? config('security.sms.default_language', 'urdu');
        $validateRateLimit = (bool) ($options['validate_rate_limit'] ?? false);

        try {
            if ($validateRateLimit && $this->wasRecentlySent($cnic, $sourceType, $phone)) {
                $this->logSms($cnic, $phone, $message, false, 'SMS already sent. Please try again later.', [], $sourceType);

                return [
                    'status' => false,
                    'message' => 'SMS already sent within the rate limit window. Please try again later.',
                ];
            }

            if (config('security.sms.disable_send', false)) {
                $response = ['status' => 'success', 'message' => 'SMS sending is disabled in configuration.'];
                $this->logSms($cnic, $phone, $message, true, 'SMS sent successfully.', $response, $sourceType);

                return ['status' => true, 'message' => 'SMS sent successfully.'];
            }

            $response = $this->callGateway($phone, $message, $language);
            $delivered = ($response['status'] ?? null) === 'success';

            $this->logSms(
                $cnic,
                $phone,
                $message,
                $delivered,
                $delivered ? 'SMS sent successfully.' : 'Failed to send SMS.',
                $response,
                $sourceType
            );

            return [
                'status' => $delivered,
                'message' => $delivered ? 'SMS sent successfully.' : 'Failed to send SMS.',
            ];
        } catch (\Throwable $e) {
            $this->logSms(
                $cnic,
                $phone,
                $message,
                false,
                'An error occurred while sending SMS',
                ['error' => $e->getMessage()],
                $sourceType
            );

            return [
                'status' => false,
                'message' => 'An error occurred while sending SMS: '.$e->getMessage(),
            ];
        }
    }

    protected function wasRecentlySent(string $cnic, string $sourceType, string $phone): bool
    {
        if ($cnic === '') {
            $cnic = $phone;
        }

        $minutes = (int) config('security.sms.rate_limit_minutes', 2);
        $rateSources = in_array($sourceType, ['forgot_password', 'resend_otp'], true)
            ? ['forgot_password', 'resend_otp']
            : [$sourceType];

        return DB::table(config('security.sms.log_table', 'sms_log'))
            ->where('cnic', $cnic)
            ->whereIn('source_type', $rateSources)
            ->where('is_delivered', 1)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    protected function callGateway(string $phone, string $message, string $language): array
    {
        $payload = http_build_query([
            'phone_no' => $phone,
            'sms_text' => $message,
            'sec_key' => config('security.sms.secret_key'),
            'sms_language' => $language,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, config('security.sms.gateway_url'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Length: '.strlen($payload)]);

        $raw = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : ['status' => 'failed', 'raw' => $raw];
    }

    protected function logSms(
        string $cnic,
        string $phone,
        string $message,
        bool $delivered,
        string $action,
        array $apiResponse,
        ?string $sourceType
    ): void {
        DB::table(config('security.sms.log_table', 'sms_log'))->insert([
            'cnic' => $cnic !== '' ? $cnic : $phone,
            'mobile_no' => $phone,
            'message' => $message,
            'is_delivered' => $delivered ? 1 : 0,
            'performed_action' => $action,
            'api_response' => json_encode($apiResponse),
            'source_type' => $sourceType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
