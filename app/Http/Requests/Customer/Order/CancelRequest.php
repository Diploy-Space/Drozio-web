<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class CancelRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
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
            'guest_id.integer' => translate('Enter a valid guest ID'),
            'order_id.required' => translate(':field is required', ['field' => translate('Order ID')]),
        ];
    }
}
