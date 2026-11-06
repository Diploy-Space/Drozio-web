<?php

namespace App\Http\Requests\Shared\OrderEdit;

use App\Http\Requests\BaseRequest;

class ProductSearchRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'searchValue' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'order_id.integer' => translate('The selected order is invalid'),
            'searchValue.string' => translate('Enter a valid search term'),
            'searchValue.max' => translate('Enter a valid search term'),
        ];
    }
}
