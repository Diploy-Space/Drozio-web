<?php

namespace App\Http\Requests\Customer\Chatting;

use App\Http\Requests\BaseRequest;

class SeenMessageRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required',
        ];
    }
}
