<?php

namespace App\Http\Requests\Customer\Refund;

use App\Http\Requests\BaseRequest;

class DetailsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Order item is required'),
            'id.integer' => translate('Please select a valid order item'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('orderDetailId') !== null) {
            $this->merge(['id' => $this->route('orderDetailId')]);
        }
    }
}
