<?php

namespace App\Http\Requests\Customer\OrderPlacement;

use App\Http\Requests\BaseRequest;

class OfflinePaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'password' => 'nullable|string',
            'method_id' => 'nullable|integer',
            'method_informations' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'order_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.integer' => translate('The selected shipping address could not be identified'),
            'billing_address_id.integer' => translate('The selected billing address could not be identified'),
            'password.string' => translate('Please enter a valid password'),
            'method_id.integer' => translate('The selected payment method could not be identified'),
            'method_informations.string' => translate('The payment information could not be read'),
            'payment_note.string' => translate('The payment note must be text'),
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

    public function methodInformations(): array
    {
        return (array) json_decode(base64_decode((string) $this->input('method_informations')));
    }
}
