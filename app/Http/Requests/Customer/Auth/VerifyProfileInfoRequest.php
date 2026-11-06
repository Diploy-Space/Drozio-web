<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class VerifyProfileInfoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|in:phone,email',
            'email_or_phone' => 'required',
            'token' => 'required',
        ];
    }
}
