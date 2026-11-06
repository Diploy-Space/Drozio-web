<?php

namespace App\Http\Requests\Shared\Auth;

use App\Http\Requests\BaseRequest;

class FirebaseVerifyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'session_info' => 'required|string',
            'phone_number' => 'required|string|max:20',
            'code' => 'required|string|max:10',
            'is_reset_token' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'session_info.required' => translate('The verification session is missing. Please request a new code'),
            'phone_number.required' => translate('Phone number is required'),
            'phone_number.max' => translate('Phone number must not be longer than 20 characters'),
            'code.required' => translate('OTP is required'),
            'code.max' => translate('OTP must not be longer than 10 characters'),
            'is_reset_token.boolean' => translate('Choose whether this code is for a password reset or a login'),
        ];
    }
}
