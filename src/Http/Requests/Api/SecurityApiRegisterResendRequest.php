<?php

namespace Pitbphp\Security\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Pitbphp\Security\Support\SecurityTier;

class SecurityApiRegisterResendRequest extends FormRequest
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
        ];
    }
}
