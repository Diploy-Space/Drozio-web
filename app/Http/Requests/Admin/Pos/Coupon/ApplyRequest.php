<?php

namespace App\Http\Requests\Admin\Pos\Coupon;

use App\Http\Requests\BaseRequest;

class ApplyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_code.required' => translate(':field is required', ['field' => translate('Coupon code')]),
            'coupon_code.string' => translate(':field must be text', ['field' => translate('Coupon code')]),
            'coupon_code.max' => translate('Coupon code must not be longer than :max characters', ['max' => 50]),
        ];
    }
}
