<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DuePaymentByWalletRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'guest_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('The guest session could not be identified'),
        ];
    }
}
