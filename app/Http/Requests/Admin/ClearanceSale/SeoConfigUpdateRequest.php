<?php

namespace App\Http\Requests\Admin\ClearanceSale;

use App\Http\Requests\BaseRequest;

class SeoConfigUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'meta_title' => 'string|nullable',
            'meta_description' => 'string|nullable',
            'meta_image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'meta_title.required' => translate('The meta title field is required'),
            'meta_description.required' => translate('The meta description field is required'),
            'meta_image.mimes' => translate('Meta image must be jpg jpeg png'),
            'meta_image.max' => translate('Meta image must not exceed 2 MB'),
        ];
    }
}
