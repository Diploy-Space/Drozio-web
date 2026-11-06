<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class UpdateCartRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'quantity' => 'required',
            ];
        }

        return [
            'key' => 'required',
            'quantity' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => translate(':field is required', ['field' => translate('Cart item')]),
            'quantity.required' => translate(':field is required', ['field' => translate('Quantity')]),
        ];
    }
}
