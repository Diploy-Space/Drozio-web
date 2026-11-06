<?php

namespace App\Http\Requests\Customer\Chatting;

use App\Http\Requests\BaseRequest;

class MessageListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'offset' => 'required',
            'limit' => 'required',
        ];
    }
}
