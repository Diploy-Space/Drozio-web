<?php

namespace App\Http\Requests\Admin\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ReviewReplyRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'reply_text' => 'required_without_all:attachments',
        ];
    }

    public function messages(): array
    {
        return [
            'required_without_all' => translate('Reply text is required').'!',
        ];
    }
}
