<?php

namespace App\Http\Requests\Deliveryman\Withdraw;

use App\Http\Requests\BaseRequest;

class AddRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0',
            'note' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => translate('Withdraw amount is required'),
            'amount.numeric' => translate('Withdraw amount must be a number'),
            'amount.gt' => translate('Withdraw amount must be greater than zero'),
            'note.max' => translate('The note must not be longer than 255 characters'),
        ];
    }
}
