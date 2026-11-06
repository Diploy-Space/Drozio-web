<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;

class DigitalProductDeleteRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'variant_key' => 'required|string',
        ];

        if (! $this->isApiRequest()) {
            $rules['product_id'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'variant_key.required' => translate('Variant key is required'),
            'variant_key.string' => translate('Variant key must be text'),
            'product_id.required' => translate('Product is required'),
        ];
    }
}
