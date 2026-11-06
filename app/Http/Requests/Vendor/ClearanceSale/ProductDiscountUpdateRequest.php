<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Enums\ClearanceDiscountType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProductDiscountUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return ['product_id' => 'required|integer'];
        }

        return [
            'product_id' => 'required|integer',
            'discount_type' => ['required', Rule::in([ClearanceDiscountType::FLAT, ClearanceDiscountType::PERCENTAGE])],
            'discount_amount' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate(':field is required', ['field' => translate('Product')]),
            'product_id.integer' => translate('The product must be a valid selection'),
            'discount_type.required' => translate('The discount type field is required'),
            'discount_type.in' => translate('The discount type must be flat or percentage'),
            'discount_amount.required' => translate('Please enter a discount amount'),
            'discount_amount.numeric' => translate('The discount amount must be a number'),
        ];
    }
}
