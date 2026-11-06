<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class CheckPhoneRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => 'required|min:6|max:20',
        ];
    }
}
