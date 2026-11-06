<?php

namespace App\Http\Requests\Customer\DeliveryMan;

use App\Http\Requests\BaseRequest;

class ReviewGetRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order ID is required.'),
            'order_id.integer' => translate('Please select a valid order'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('orderId') !== null) {
            $this->merge(['order_id' => $this->route('orderId')]);
        }
    }
}
