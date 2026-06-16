<?php

namespace Pitbphp\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Rules\ValidCaptcha;

class SecurityRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = config('security.user.table', 'users');
        $methods = config('security.mfa.methods', ['email', 'sms']);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:{$table},email"],
            'password' => ['required', 'confirmed', new PitbPassword],
            'mfa_method' => ['required', 'string', Rule::in($methods)],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if ($this->input('mfa_method') === 'sms') {
            $rules['phone'] = ['required', 'string', 'max:30'];
        }

        if (config('security.captcha.enabled', true)) {
            $field = config('security.captcha.field', 'captcha');
            $rules[$field] = ['required', 'string', new ValidCaptcha];
        }

        return $rules;
    }
}
