<?php

namespace App\Http\Requests\Vendor\Coupon;

use App\Http\Requests\BaseRequest;

class CheckRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'order_amount' => ['required', 'numeric', 'min:0'],
            'user_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => translate('Enter a coupon code'),
            'code.max' => translate('The coupon code is too long'),
            'order_amount.required' => translate('The order amount is required'),
            'order_amount.numeric' => translate('The order amount must be a number'),
            'order_amount.min' => translate('The order amount cannot be negative'),
            'user_id.integer' => translate('Choose a valid customer'),
        ];
    }
}
