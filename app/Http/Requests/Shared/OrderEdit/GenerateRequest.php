<?php

namespace App\Http\Requests\Shared\OrderEdit;

use App\Http\Requests\BaseRequest;

class GenerateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'products' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'order_id.integer' => translate('The selected order is invalid'),
            'products.array' => translate('Add at least one product to the order'),
        ];
    }
}
