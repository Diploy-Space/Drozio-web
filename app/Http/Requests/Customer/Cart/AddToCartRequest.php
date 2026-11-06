<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class AddToCartRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required',
            'quantity' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate(':field is required', ['field' => translate('Product')]),
            'quantity.required' => translate(':field is required', ['field' => translate('Quantity')]),
        ];
    }
}
