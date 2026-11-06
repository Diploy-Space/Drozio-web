<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DuePaymentByCodRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'guest_id' => 'nullable|integer',
            'bring_change_amount' => 'nullable|numeric|min:0',
            'current_currency_code' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('The guest session could not be identified'),
            'bring_change_amount.numeric' => translate('The change amount must be a number'),
            'bring_change_amount.min' => translate('The change amount cannot be negative'),
            'current_currency_code.string' => translate('The selected currency could not be identified'),
        ];
    }
}
