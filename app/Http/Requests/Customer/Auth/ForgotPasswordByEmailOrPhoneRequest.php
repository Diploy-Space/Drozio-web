<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class ForgotPasswordByEmailOrPhoneRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email_or_phone' => 'required',
            'type' => 'required|in:phone,email',
        ];
    }
}
