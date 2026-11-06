<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class RemoveFromCartRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [];
        }

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
