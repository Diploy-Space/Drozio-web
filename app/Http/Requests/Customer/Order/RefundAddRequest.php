<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RefundAddRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_details_id' => 'required',
            'amount' => 'required',
            'refund_reason' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'order_details_id.required' => translate('Order item is required'),
            'amount.required' => translate(':field is required', ['field' => translate('Amount')]),
            'refund_reason.required' => translate('Refund reason is required'),
        ];
    }
}
