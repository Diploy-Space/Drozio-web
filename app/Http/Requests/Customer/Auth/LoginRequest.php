<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email_or_phone' => 'required|string|max:100',
            'password' => 'required|min:6',
            'type' => 'nullable|in:phone,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email_or_phone.required' => translate('Phone number or email is required'),
            'email_or_phone.max' => translate('Phone number or email must not be longer than 100 characters'),
            'password.required' => translate('Password is required'),
            'password.min' => translate('Password must be at least 6 characters'),
            'type.in' => translate('The login type must be either phone or email'),
        ];
    }
}
