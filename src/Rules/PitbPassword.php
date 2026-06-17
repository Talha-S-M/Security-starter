<?php

namespace Pitbphp\Security\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Pitbphp\Security\Services\PasswordHistoryService;
use Pitbphp\Security\Support\PasswordStrength;

class PitbPassword implements ValidationRule
{
    public function __construct(
        protected ?Model $subject = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = PasswordStrength::firstViolation((string) $value);

        if ($message !== null) {
            $fail($message);

            return;
        }

        if ($this->subject && app(PasswordHistoryService::class)->wasRecentlyUsed($this->subject, (string) $value)) {
            $count = (int) config('security.password.history_count', 3);
            $fail("You may not reuse any of your last {$count} passwords.");
        }
    }
}
