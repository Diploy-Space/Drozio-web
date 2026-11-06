<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DuePaymentByDigitalPaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'guest_id' => 'nullable|integer',
            'payment_method' => 'required|string',
            'payment_platform' => 'nullable|string',
            'payment_request_from' => 'nullable|string',
            'current_currency_code' => 'required|string',
            'order_due_payment_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('The guest session could not be identified'),
            'payment_method.required' => translate('Payment method is required'),
            'payment_method.string' => translate('The selected payment method could not be identified'),
            'payment_platform.string' => translate('The payment platform could not be identified'),
            'payment_request_from.string' => translate('The payment source could not be identified'),
            'current_currency_code.required' => translate('Currency is required'),
            'current_currency_code.string' => translate('The selected currency could not be identified'),
            'order_due_payment_note.string' => translate('The payment note must be text'),
        ];
    }
}
