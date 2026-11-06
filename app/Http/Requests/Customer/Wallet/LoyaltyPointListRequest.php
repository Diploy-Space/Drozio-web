<?php

namespace App\Http\Requests\Customer\Wallet;

use App\Http\Requests\BaseRequest;

class LoyaltyPointListRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'limit' => 'required',
                'offset' => 'required',
            ];
        }

        return [
            'types' => 'nullable|array',
            'transaction_range' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'limit.required' => translate('The limit is required'),
            'offset.required' => translate('The offset is required'),
            'types.array' => translate('Please select valid transaction types'),
            'transaction_range.string' => translate('Invalid date range format'),
        ];
    }
}
