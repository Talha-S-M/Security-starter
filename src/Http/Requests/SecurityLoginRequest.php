<?php

namespace Pitbphp\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Pitbphp\Security\Rules\ValidCaptcha;

class SecurityLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        if (config('security.captcha.enabled', true)) {
            $field = config('security.captcha.field', 'captcha');
            $rules[$field] = ['required', 'string', new ValidCaptcha];
        }

        return $rules;
    }
}
