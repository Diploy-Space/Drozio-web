<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            ...($this->isApiRequest() ? [] : ['id' => 'required']),
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Select a product to update'),
            'status.required' => translate('Product status is required'),
            'status.boolean' => translate('Product status must be active or inactive'),
        ];
    }
}
