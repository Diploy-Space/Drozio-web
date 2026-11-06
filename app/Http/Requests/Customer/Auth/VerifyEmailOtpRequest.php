<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class VerifyEmailOtpRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required',
            'token' => 'required',
        ];
    }
}
