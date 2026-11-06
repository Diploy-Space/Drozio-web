<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class PasswordResetRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8',
        ];

        if ($this->isApiRequest()) {
            $rules['identity'] = 'required|string|max:100';
            $rules['otp'] = 'required|string|max:10';

            return $rules;
        }

        $rules['reset_token'] = 'required|string';
        $rules['identity'] = 'required|string';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Phone number or email is required'),
            'otp.required' => translate('OTP is required'),
            'otp.max' => translate('OTP must not be longer than 10 characters'),
            'reset_token.required' => translate('This password reset link is no longer valid'),
            'password.required' => translate('New password is required'),
            'password.min' => translate('New password must be at least 8 characters'),
            'password.same' => translate('Password and confirm password do not match'),
            'confirm_password.required' => translate('Confirm password is required'),
            'confirm_password.min' => translate('Confirm password must be at least 8 characters'),
        ];
    }
}
