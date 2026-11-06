<?php

namespace App\Http\Requests\Admin\Wallet;

use App\Http\Requests\BaseRequest;

class AddFundRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:.01|max:10000000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => translate('Select a customer to add fund'),
            'customer_id.exists' => translate('The selected customer does not exist'),
            'amount.required' => translate('Amount field is required'),
            'amount.numeric' => translate('The amount must be a number'),
            'amount.min' => translate('The amount must be at least 0.01'),
            'amount.max' => translate('The amount cannot exceed 10000000'),
        ];
    }
}
