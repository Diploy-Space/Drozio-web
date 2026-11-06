<?php

namespace App\Http\Requests\Vendor\Review;

use App\Http\Requests\BaseRequest;

class ReplyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reply_text' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply_text.required' => translate('Write a reply to the review'),
            'reply_text.string' => translate('Write the reply as plain text'),
        ];
    }
}
