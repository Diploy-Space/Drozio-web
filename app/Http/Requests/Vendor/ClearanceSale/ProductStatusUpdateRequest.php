<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Http\Requests\BaseRequest;

class ProductStatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate(':field is required', ['field' => translate('Product')]),
            'product_id.integer' => translate('The product must be a valid selection'),
            'status.required' => translate(':field is required', ['field' => translate('Status')]),
            'status.boolean' => translate('The status must be either true or false'),
        ];
    }
}
