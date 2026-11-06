<?php

namespace Modules\AI\app\Http\Requests;

class ProductPricingRequest extends ProductGenerationRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Product name and description are required to generate pricing'),
            'name.string' => translate('Product name and description are required to generate pricing'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'description.string' => translate('Product description must be text to generate pricing'),
        ];
    }
}
