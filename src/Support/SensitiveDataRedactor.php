<?php

namespace Pitbphp\Security\Support;

class SensitiveDataRedactor
{
    public static function redact(array $data): array
    {
        $sensitive = array_map('strtolower', (array) config('security.logging.redact_keys', []));

        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, $sensitive, true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }

        return $data;
    }
}
