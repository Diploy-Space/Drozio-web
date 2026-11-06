<?php

namespace App\Http\Requests\Customer\Wishlist;

use App\Http\Requests\BaseRequest;

class ToggleRequest extends BaseRequest
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
            'product_id.required' => translate('Please select a product'),
            'product_id.integer' => translate('The selected product is not valid'),
        ];
    }
}
