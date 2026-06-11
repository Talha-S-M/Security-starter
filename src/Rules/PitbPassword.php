<?php

namespace Pitbphp\Security\Rules;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;
use Pitbphp\Security\Services\PasswordHistoryService;

class PitbPassword implements ValidationRule
{
    public function __construct(
        protected ?Model $subject = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rule = Password::min((int) config('security.password.min_length', 12));

        if (config('security.password.require_uppercase') && config('security.password.require_lowercase')) {
            $rule->mixedCase();
        } elseif (config('security.password.require_uppercase') || config('security.password.require_lowercase')) {
            $rule->letters();
        }

        if (config('security.password.require_numbers')) {
            $rule->numbers();
        }

        if (config('security.password.require_symbols')) {
            $rule->symbols();
        }

        $validator = validator([$attribute => $value], [$attribute => $rule]);

        if ($validator->fails()) {
            $fail($validator->errors()->first($attribute));

            return;
        }

        if ($this->subject && app(PasswordHistoryService::class)->wasRecentlyUsed($this->subject, (string) $value)) {
            $count = (int) config('security.password.history_count', 3);
            $fail("You may not reuse any of your last {$count} passwords.");
        }
    }
}
