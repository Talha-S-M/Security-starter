<?php

namespace Pitbphp\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Pitbphp\Security\Support\SecurityTier;

class SecurityRegisterVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SecurityTier::registrationEnabled()
            && SecurityTier::registrationUsesOtp()
            && $this->session()->get('security.registration.step') === 'verify';
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:'.config('security.registration.otp_length', 6)],
        ];
    }
}
