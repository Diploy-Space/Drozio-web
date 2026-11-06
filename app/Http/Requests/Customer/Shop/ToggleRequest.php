<?php

namespace App\Http\Requests\Customer\Shop;

use App\Http\Requests\BaseRequest;

class ToggleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => translate('Please select a shop'),
            'shop_id.integer' => translate('The selected shop is not valid'),
        ];
    }
}
