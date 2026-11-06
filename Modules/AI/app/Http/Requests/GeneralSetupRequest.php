<?php

namespace Modules\AI\app\Http\Requests;

class GeneralSetupRequest extends ProductGenerationRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Product name is required to generate general setup'),
            'name.string' => translate('Product name is required to generate general setup'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'description.required' => translate('Product description is required to generate general setup'),
            'description.string' => translate('Product description is required to generate general setup'),
        ];
    }
}
