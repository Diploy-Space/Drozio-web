<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class ResetTokenVerificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'identity' => 'required|string|max:100',
            'reset_token' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Phone number or email is required'),
            'identity.max' => translate('Phone number or email must not be longer than 100 characters'),
            'reset_token.required' => translate('OTP is required'),
            'reset_token.max' => translate('OTP must not be longer than 10 characters'),
        ];
    }
}
