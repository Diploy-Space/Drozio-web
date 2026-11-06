<?php

namespace App\Http\Requests\Admin\Report\VendorTax;

class DetailsRequest extends FilterRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'shop_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'shop_id.required' => translate('Choose a vendor'),
            'shop_id.integer' => translate('Choose a valid vendor'),
            'shop_id.min' => translate('Choose a valid vendor'),
        ];
    }
}
