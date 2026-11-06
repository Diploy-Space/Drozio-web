<?php

namespace App\Http\Requests\Admin\DeliveryMan;

use App\Http\Requests\BaseRequest;

class CashCollectRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

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
