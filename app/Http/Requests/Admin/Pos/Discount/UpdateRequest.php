<?php

namespace App\Http\Requests\Admin\Pos\Discount;

use App\Http\Requests\BaseRequest;
use App\Services\New\Pos\PosCartService;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $discountRules = ['required', 'numeric', 'gt:0'];

        if ($this->input('type') === PosCartService::EXTRA_DISCOUNT_TYPE_PERCENT) {
            $discountRules[] = 'max:100';
        }

        return [
            'type' => ['required', Rule::in([PosCartService::EXTRA_DISCOUNT_TYPE_AMOUNT, PosCartService::EXTRA_DISCOUNT_TYPE_PERCENT])],
            'discount' => $discountRules,
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => translate(':field is required', ['field' => translate('Discount type')]),
            'type.in' => translate('Discount type must be amount or percent'),
            'discount.required' => translate(':field is required', ['field' => translate('Discount')]),
            'discount.numeric' => translate('Discount must be a number'),
            'discount.gt' => translate('Discount must be greater than 0'),
            'discount.max' => translate('Extra discount can not be more than 100 percent'),
        ];
    }
}
