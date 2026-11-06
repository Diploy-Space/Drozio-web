<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class RemoveAllFromCartRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => translate(':field is required', ['field' => translate('Cart item')]),
        ];
    }
}
