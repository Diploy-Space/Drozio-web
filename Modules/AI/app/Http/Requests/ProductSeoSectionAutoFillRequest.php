<?php

namespace Modules\AI\app\Http\Requests;

class ProductSeoSectionAutoFillRequest extends ProductGenerationRequest
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
            'name.required' => translate('Product name is required to generate seo information'),
            'name.string' => translate('Product name is required to generate seo information'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'description.string' => translate('Product description is required to generate seo information'),
        ];
    }
}
