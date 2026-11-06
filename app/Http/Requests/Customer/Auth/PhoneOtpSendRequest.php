<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class PhoneOtpSendRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'temporary_token' => 'required',
            'phone' => 'required|min:11|max:14',
        ];
    }
}
