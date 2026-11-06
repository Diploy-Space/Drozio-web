<?php

namespace Modules\AI\app\Http\Requests\Shared\WholesaleProduct;

use App\Http\Requests\BaseRequest;

class PricingSetupAutoFillRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'has_variation' => 'nullable|boolean',
            'variations' => 'nullable|array',
            'variations.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Product name is required to generate pricing setup'),
            'name.string' => translate('Product name is required to generate pricing setup'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'description.required' => translate('Product description is required to generate pricing setup'),
            'description.string' => translate('Product description is required to generate pricing setup'),
            'has_variation.boolean' => translate('Please tell whether this product has variations before generating the pricing setup.'),
            'variations.array' => translate('Please send the product variations as a list to generate the pricing setup.'),
            'variations.*.string' => translate('Each product variation must be written as text to generate the pricing setup.'),
            'variations.*.max' => translate('Each product variation must be 255 characters or fewer.'),
        ];
    }

    public function hasVariation(): ?bool
    {
        return $this->has('has_variation') ? $this->boolean('has_variation') : null;
    }

    public function variations(): array
    {
        return array_values(array_filter((array) $this->input('variations', []), fn ($variation): bool => is_string($variation) && trim($variation) !== ''));
    }
}
