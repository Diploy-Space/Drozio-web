<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class DuePaymentRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required',
            'payment_method' => 'required',
            'payment_platform' => 'required',
            'order_due_payment_note' => 'nullable|string',
            'method_id' => 'required_if:payment_method,offline_payment',
            'payment_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'payment_method.required' => translate('Payment method is required'),
            'payment_platform.required' => translate('Payment platform is required'),
            'method_id.required_if' => translate('Offline payment method is required'),
        ];
    }
}
