<?php

namespace App\Http\Requests\Admin\Promotion\FlashDeal;

use App\Http\Requests\BaseRequest;

class AddProductRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'array'],
            'product_id.*' => ['integer'],
            'discount' => ['nullable', ...$this->numericRule()],
            'discount_type' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate('Please select at least one product'),
            'product_id.array' => translate('Please select at least one product'),
            'product_id.*.integer' => translate('The selected product is not valid'),
            'discount.numeric' => translate('Discount must be a number'),
            'discount.min' => translate('Discount cannot be negative'),
            'discount.max' => translate('Discount is too large'),
        ];
    }
}
