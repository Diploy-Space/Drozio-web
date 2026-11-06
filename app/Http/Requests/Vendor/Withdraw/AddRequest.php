<?php

namespace App\Http\Requests\Vendor\Withdraw;

use App\Http\Requests\BaseRequest;

class AddRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('withdraw_method') && $this->has('withdraw_method_id')) {
            $this->merge(['withdraw_method' => $this['withdraw_method_id']]);
        }
    }

    public function rules(): array
    {
        return [
            'withdraw_method' => ['required', 'integer', 'exists:withdrawal_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'withdraw_method.required' => translate('Choose a withdrawal method'),
            'withdraw_method.exists' => translate('This withdrawal method is not available'),
            'amount.required' => translate('The amount is required'),
            'amount.numeric' => translate('The amount must be a number'),
            'amount.gt' => translate('Amount must be greater than zero'),
        ];
    }
}
