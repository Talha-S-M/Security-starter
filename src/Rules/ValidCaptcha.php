<?php

namespace Pitbphp\Security\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('security.captcha.enabled', true)) {
            return;
        }

        if (! class_exists(\Mews\Captcha\Facades\Captcha::class)) {
            return;
        }

        if (! captcha_check($value)) {
            $fail('The CAPTCHA verification failed. Please try again.');
        }
    }
}
