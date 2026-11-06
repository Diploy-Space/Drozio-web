<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class VerifyPhoneOtpRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => 'required',
            'token' => 'required',
        ];
    }
}
