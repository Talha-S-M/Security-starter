<?php

namespace Pitbphp\Security\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Pitbphp\Security\Support\SecurityTier;

class SecurityApiRegisterVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SecurityTier::registrationEnabled()
            && SecurityTier::registrationUsesOtp();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:'.config('security.registration.otp_length', 6)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
