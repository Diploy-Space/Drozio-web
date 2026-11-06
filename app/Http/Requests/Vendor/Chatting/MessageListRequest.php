<?php

namespace App\Http\Requests\Vendor\Chatting;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class MessageListRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'offset' => 'required',
            'limit' => 'required',
        ];
    }
}
