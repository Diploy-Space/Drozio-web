<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;

class QuantityUpdateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $variation = $this->input('variation');

        if ($this->isApiRequest() && is_string($variation) && $variation !== '') {
            $decoded = json_decode($variation, true);
            $this->merge(['variation' => is_array($decoded) ? $decoded : $variation]);
        }
    }

    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'product_id' => 'required|exists:products,id',
                'current_stock' => 'required|numeric|min:0',
            ];
        }

        return [
            'current_stock' => 'required|integer|min:0',
            'variation' => 'nullable|array',
            'variation.*.type' => 'required|string',
            'variation.*.price' => 'required|numeric',
            'variation.*.sku' => 'nullable|string',
            'variation.*.qty' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate('Select a product to update'),
            'product_id.exists' => translate('Product not found'),
            'current_stock.required' => translate('Stock quantity is required'),
            'current_stock.numeric' => translate('Stock quantity must be a number'),
            'current_stock.integer' => translate('Stock quantity must be a whole number'),
            'current_stock.min' => translate('Stock quantity cannot be negative'),
            'variation.array' => translate('Variation stock must be a list of variations'),
            'variation.*.type.required' => translate('Each variation needs a type'),
            'variation.*.type.string' => translate('Each variation type must be text'),
            'variation.*.price.required' => translate('Each variation needs a price'),
            'variation.*.price.numeric' => translate('Each variation price must be a number'),
            'variation.*.sku.string' => translate('Each variation SKU must be text'),
            'variation.*.qty.required' => translate('Each variation needs a quantity'),
            'variation.*.qty.integer' => translate('Each variation quantity must be a whole number'),
            'variation.*.qty.min' => translate('Variation quantity cannot be negative'),
        ];
    }

    public function variations(): array
    {
        return array_values((array) ($this->validated('variation') ?? []));
    }
}
