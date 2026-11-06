<?php

namespace App\Http\Requests\Deliveryman\Auth;

use App\Http\Requests\BaseRequest;

class OtpVerificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'identity' => 'required|string|max:100',
            'otp' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Phone number or email is required'),
            'identity.max' => translate('Phone number or email must not be longer than 100 characters'),
            'otp.required' => translate('OTP is required'),
            'otp.max' => translate('OTP must not be longer than 10 characters'),
        ];
    }
}
