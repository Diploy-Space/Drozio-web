<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RazorPayCreateOrderRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'payment_amount' => 'required|numeric',
            'currency_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_amount.required' => translate('Payment amount is required'),
            'payment_amount.numeric' => translate('Payment amount must be a number'),
            'currency_code.required' => translate(':field is required', ['field' => translate('Currency code')]),
        ];
    }
}
