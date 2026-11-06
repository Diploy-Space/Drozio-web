<?php

namespace App\Http\Requests\Shared\OrderEdit;

use App\Http\Requests\BaseRequest;

class ProductListUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['array'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'order_id.integer' => translate('The selected order is invalid'),
            'products.required' => translate('Add at least one product to the order'),
            'products.array' => translate('Add at least one product to the order'),
            'products.min' => translate('Add at least one product to the order'),
            'products.*.array' => translate('Enter a quantity for every product'),
            'products.*.qty.required' => translate('Enter a quantity for every product'),
            'products.*.qty.integer' => translate('The quantity must be a whole number'),
            'products.*.qty.min' => translate('The quantity must be at least one'),
        ];
    }
}
