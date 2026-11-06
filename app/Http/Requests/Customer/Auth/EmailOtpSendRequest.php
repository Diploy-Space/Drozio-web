<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class EmailOtpSendRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'temporary_token' => 'required',
            'email' => 'required',
        ];
    }
}
