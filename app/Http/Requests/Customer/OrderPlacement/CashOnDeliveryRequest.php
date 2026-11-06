<?php

namespace App\Http\Requests\Customer\OrderPlacement;

use App\Http\Requests\BaseRequest;

class CashOnDeliveryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'password' => 'nullable|string',
            'bring_change_amount' => 'nullable|numeric',
            'current_currency_code' => 'nullable|string',
            'order_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.integer' => translate('The selected shipping address could not be identified'),
            'billing_address_id.integer' => translate('The selected billing address could not be identified'),
            'password.string' => translate('Please enter a valid password'),
            'bring_change_amount.numeric' => translate('The change amount must be a number'),
            'current_currency_code.string' => translate('The selected currency could not be identified'),
            'order_note.string' => translate('The order note must be text'),
        ];
    }

    public function couponCode(): string
    {
        return is_scalar($this->input('coupon_code')) ? (string) $this->input('coupon_code') : '';
    }

    public function isCreateAccountRequested(): bool
    {
        return (bool) $this->input('is_check_create_account');
    }
}
