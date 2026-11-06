<?php

namespace Modules\AI\app\Http\Requests;

class GenerateTitleFromImageRequest extends ProductGenerationRequest
{
    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => translate('Image is required for analysis.'),
            'image.image' => translate('The uploaded file must be an image.'),
            'image.mimes' => translate('Only JPEG, PNG, JPG, and GIF images are allowed.'),
            'image.max' => translate('Image size must not exceed 1MB.'),
        ];
    }
}
