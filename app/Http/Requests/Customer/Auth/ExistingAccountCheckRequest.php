<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class ExistingAccountCheckRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'user_response' => 'required|in:0,1',
            'medium' => 'required|in:google,facebook,apple',
        ];
    }
}
