<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Enums\ClearanceDiscountType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProductAddRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'productIds' => 'array',
                'productIds.*' => 'integer',
            ];
        }

        return [
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.discount_type' => ['nullable', Rule::in([ClearanceDiscountType::FLAT, ClearanceDiscountType::PERCENTAGE])],
            'products.*.discount_amount' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'productIds.array' => translate('The product list must be a list of products'),
            'productIds.*.integer' => translate('The product must be a valid selection'),
            'products.required' => translate('Select at least one product'),
            'products.array' => translate('The product list must be a list of products'),
            'products.min' => translate('Select at least one product'),
            'products.*.product_id.required' => translate(':field is required', ['field' => translate('Product')]),
            'products.*.product_id.integer' => translate('The product must be a valid selection'),
            'products.*.discount_type.in' => translate('The discount type must be flat or percentage'),
            'products.*.discount_amount.numeric' => translate('The discount amount must be a number'),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function productIds(): array
    {
        return array_map('intval', $this->isApiRequest()
            ? array_column((array) $this['products'], 'product_id')
            : (array) $this['productIds']);
    }

    /**
     * @return array<int, array{discount_type: string, discount_amount: mixed}>
     */
    public function discountInputs(): array
    {
        if (! $this->isApiRequest()) {
            return $this->panelDiscountInputs();
        }

        $inputs = [];

        foreach ((array) $this['products'] as $product) {
            $inputs[(int) $product['product_id']] = [
                'discount_type' => $product['discount_type'] ?? ClearanceDiscountType::PERCENTAGE,
                'discount_amount' => $product['discount_amount'] ?? null,
            ];
        }

        return $inputs;
    }

    /**
     * @return array<int, array{discount_type: string, discount_amount: mixed}>
     */
    private function panelDiscountInputs(): array
    {
        $inputs = [];

        foreach ($this->productIds() as $productId) {
            $inputs[$productId] = [
                'discount_type' => $this['discount_type'][$productId] ?? ClearanceDiscountType::PERCENTAGE,
                'discount_amount' => $this['discount_amount'][$productId] ?? null,
            ];
        }

        return $inputs;
    }
}
