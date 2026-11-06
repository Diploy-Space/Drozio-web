<?php

namespace App\Http\Requests\Deliveryman\Auth;

use App\Http\Requests\BaseRequest;

class PasswordResetRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'identity' => 'required|string|max:100',
            'otp' => 'required|string|max:10',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Phone number or email is required'),
            'identity.max' => translate('Phone number or email must not be longer than 100 characters'),
            'otp.required' => translate('OTP is required'),
            'otp.max' => translate('OTP must not be longer than 10 characters'),
            'password.required' => translate('New password is required'),
            'password.min' => translate('New password must be at least 8 characters'),
            'password.same' => translate('New password and confirm password do not match'),
            'confirm_password.required' => translate('Confirm password is required'),
            'confirm_password.min' => translate('Confirm password must be at least 8 characters'),
        ];
    }
}
