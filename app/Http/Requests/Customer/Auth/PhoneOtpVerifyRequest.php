<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class PhoneOtpVerifyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => 'required',
            'temporary_token' => 'required',
            'otp' => 'required',
        ];
    }
}
