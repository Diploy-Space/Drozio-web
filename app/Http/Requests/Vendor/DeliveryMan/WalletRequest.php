<?php

namespace App\Http\Requests\Vendor\DeliveryMan;

use App\Http\Requests\BaseRequest;

class WalletRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => translate(':field is required', ['field' => translate('Amount')]),
            'amount.numeric' => translate('Amount must be a number'),
            'amount.gt' => translate('Amount must be greater than zero'),
        ];
    }
}
