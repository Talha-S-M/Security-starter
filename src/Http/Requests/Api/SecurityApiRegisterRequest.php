<?php

namespace Pitbphp\Security\Http\Requests\Api;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Support\SecurityTier;

class SecurityApiRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SecurityTier::registrationEnabled();
    }

    public function rules(): array
    {
        $table = config('security.user.table', 'users');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                "unique:{$table},email",
            ],
            'password' => ['required', 'confirmed', new PitbPassword],
        ];

        if (SecurityTier::registrationRequiresApproval()) {
            $rules['email'][] = function (string $attribute, mixed $value, Closure $fail) {
                if (app(AccessProvisioningService::class)->hasPendingRegistration((string) $value)) {
                    $fail('A registration request for this email is already pending approval.');
                }
            };
        }

        return $rules;
    }
}
