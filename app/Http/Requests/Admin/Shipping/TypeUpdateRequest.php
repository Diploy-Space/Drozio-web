<?php

namespace App\Http\Requests\Admin\Shipping;

use App\Http\Requests\BaseRequest;

class TypeUpdateRequest extends BaseRequest
{
    /**
     * @return array
     *               Get the validation error message
     */
    public function rules(): array
    {
        return [
            'shippingType' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'shippingType.required' => translate(':field is required', ['field' => translate('Shipping type')]),
        ];
    }
}
