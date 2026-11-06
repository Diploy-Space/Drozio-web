<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DuePaymentByOfflinePaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'guest_id' => 'nullable|integer',
            'method_id' => 'required|integer',
            'method_informations' => 'nullable|string',
            'order_due_payment_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('The guest session could not be identified'),
            'method_id.required' => translate('Offline payment method is required'),
            'method_id.integer' => translate('The selected payment method could not be identified'),
            'method_informations.string' => translate('The payment information could not be read'),
            'order_due_payment_note.string' => translate('The payment note must be text'),
        ];
    }

    public function methodInformations(): array
    {
        return (array) json_decode(base64_decode((string) $this->input('method_informations')));
    }
}
