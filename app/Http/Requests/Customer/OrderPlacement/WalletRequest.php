<?php

namespace App\Http\Requests\Customer\OrderPlacement;

use App\Http\Requests\BaseRequest;

class WalletRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'payment_note' => 'nullable|string',
            'order_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.integer' => translate('The selected shipping address could not be identified'),
            'billing_address_id.integer' => translate('The selected billing address could not be identified'),
            'payment_note.string' => translate('The payment note must be text'),
            'order_note.string' => translate('The order note must be text'),
        ];
    }

    public function couponCode(): string
    {
        return is_scalar($this->input('coupon_code')) ? (string) $this->input('coupon_code') : '';
    }
}
