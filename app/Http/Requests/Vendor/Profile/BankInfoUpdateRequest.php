<?php

namespace App\Http\Requests\Vendor\Profile;

use App\Http\Requests\BaseRequest;

class BankInfoUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bank_name' => 'required',
            'branch' => 'required',
            'holder_name' => 'required',
            'account_no' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => translate('Bank name is required'),
            'branch.required' => translate('Branch is required'),
            'holder_name.required' => translate('Holder name is required'),
            'account_no.required' => translate('Account number is required'),
        ];
    }
}
