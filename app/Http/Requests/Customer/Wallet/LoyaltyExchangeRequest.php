<?php

namespace App\Http\Requests\Customer\Wallet;

use App\Http\Requests\BaseRequest;

class LoyaltyExchangeRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $rules = [
            'point' => 'required|numeric|min:1',
        ];

        if ($this->isApiRequest()) {
            $rules['point'] = 'required|integer|min:1';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'point.required' => translate('The point field is required'),
            'point.numeric' => translate('The point field must be a number'),
            'point.min' => translate('The point field must be at least one'),
        ];
    }
}
