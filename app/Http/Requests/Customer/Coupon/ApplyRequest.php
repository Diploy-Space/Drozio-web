<?php

namespace App\Http\Requests\Customer\Coupon;

use App\Http\Requests\BaseRequest;

class ApplyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => translate('Please enter coupon code'),
            'code.string' => translate('Invalid coupon'),
        ];
    }
}
