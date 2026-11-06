<?php

namespace App\Http\Requests\Shared\OrderEdit;

use App\Http\Requests\BaseRequest;

class ProductRemoveRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'variant' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'order_id.integer' => translate('The selected order is invalid'),
            'product_id.required' => translate('Choose a product'),
            'product_id.integer' => translate('Choose a valid product'),
            'variant.string' => translate('Choose a valid variant'),
            'variant.max' => translate('Choose a valid variant'),
        ];
    }
}
