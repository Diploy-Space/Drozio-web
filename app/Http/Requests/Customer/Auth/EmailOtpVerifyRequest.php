<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class EmailOtpVerifyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required',
            'temporary_token' => 'required',
            'token' => 'required',
        ];
    }
}
