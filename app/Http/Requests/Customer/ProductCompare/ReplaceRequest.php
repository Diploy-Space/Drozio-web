<?php

namespace App\Http\Requests\Customer\ProductCompare;

use App\Http\Requests\BaseRequest;

class ReplaceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate('Product is required'),
            'product_id.integer' => translate('The selected product is not valid'),
        ];
    }
}
