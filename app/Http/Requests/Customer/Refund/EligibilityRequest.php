<?php

namespace App\Http\Requests\Customer\Refund;

use App\Http\Requests\BaseRequest;

class EligibilityRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_details_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'order_details_id.required' => translate('Order item is required'),
            'order_details_id.integer' => translate('Please select a valid order item'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('orderDetailId') !== null) {
            $this->merge(['order_details_id' => $this->route('orderDetailId')]);
        }
    }
}
