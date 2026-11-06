<?php

namespace Modules\AI\app\Http\Requests;

class ProductTitleAutoFillRequest extends ProductGenerationRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'langCode' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Product name is required to generate product name'),
            'name.string' => translate('Product name is required to generate product name'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'langCode.string' => translate('Please choose a valid language'),
            'langCode.max' => translate('Please choose a valid language'),
        ];
    }
}
