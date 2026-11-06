<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class TrackRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'order_id' => 'required|integer',
                'phone_number' => 'nullable|string',
                'guest_id' => 'nullable|integer',
            ];
        }

        return [
            'order_id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate(':field is required', ['field' => translate('Order ID')]),
            'order_id.integer' => translate('Enter a valid order ID'),
            'phone_number.string' => translate('Enter a valid phone number'),
            'guest_id.integer' => translate('Enter a valid guest ID'),
        ];
    }
}
