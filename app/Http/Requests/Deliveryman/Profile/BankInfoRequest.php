<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;

class BankInfoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'bank_name' => 'nullable|string|max:191',
            'branch' => 'nullable|string|max:191',
            'account_no' => 'nullable|string|max:191',
            'holder_name' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.max' => translate('Bank name must not be longer than 191 characters'),
            'branch.max' => translate('Branch must not be longer than 191 characters'),
            'account_no.max' => translate('Account number must not be longer than 191 characters'),
            'holder_name.max' => translate('Account holder name must not be longer than 191 characters'),
        ];
    }
}
