<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\BaseRequest;

class PasswordResetRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'reset_token.required' => translate('This password reset link is no longer valid'),
            'password.required' => translate('New password is required'),
            'password.min' => translate('New password must be at least 8 characters'),
            'password.same' => translate('New password and confirm password do not match'),
            'confirm_password.required' => translate('Confirm password is required'),
            'confirm_password.min' => translate('Confirm password must be at least 8 characters'),
        ];
    }
}
